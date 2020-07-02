<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use App\Price;
use App\Quantity;
use App\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{

  /**
   * Show all activities
   */
  public function index()
  {
    return Activity::with('subcategory')->with('professional')->with('tags')->get();
  }

  /**
   * Show an activity
   */
  public function show($id)
  {
    $activity = Activity::with('postal_code')->with('subcategory')->with('professional')->with('tags')->with('prices')->with('prices.quantity')->find($id);
    $user = Auth::user();
    if ($activity && $user) {
      // Show state only if the user in not an customer
      if (!$user->isCustomer()) {
        return $activity->load('state');
      } else {
        if (Activity::find($id)->isAccepted()) {
          return $activity;
        } else {
          return response([
            'error' => true,
            'messages' => ["L'activité demandé n'a pas encore été accepté"]
          ]);
        }
      }
    } else {
      return response([
        'error' => true,
        'messages' => ["L'activité demandé n'existe pas"]
      ]);
    }
  }

  /**
   * Create an activity
   */
  public function store(Request $request)
  {

    // Validate parameters
    $validator = Validator::make($request->all(), [
      'name' => 'string|max:55|required',
      'address' => 'string|max:55|required',
      'siren' => 'size:9|required',
      'phone_number' => 'size:10|required',
      'opening_hours' => 'digits_between:4,5|required',
      'closing_hours' => 'digits_between:4,5|required',
      'average_time_spent' => 'digits_between:4,5|required',
      'disabled_access' => 'boolean|required',
      'subcategory.id' => 'exists:subcategories,id|required',
      'tags.*.id' => 'exists:tags,id|required',
      'postal_code.id' => 'exists:postal_codes,id|required',
      'professional.id' => 'exists:professionals,id',
      'state.id' => 'exists:states,id|required',
      'quantity.id' => 'exists:quantities,id|required',
      'price.amount' => [
        'required',
        'regex:/^\d+(\.\d{1,2})?$/'
      ]
    ]);

    // Send errors if the validator failed
    if ($validator->fails()) {
      return response([
        "error" => true,
        "messages" => $validator->messages()
      ]);
    } else {
      // Check if SIREN exists
      // Get the url from the constant file
      $url = Config::get('constants.API.Siren');
      $response = Http::get($url . $request->input('siren'));
      if ($response->status() == 404) {
        return response([
          "error" => true,
          "messages" => "Le numéro de Siren saisie n'est pas valide."
        ]);
      } else {
        // Create activity
        $activity = new Activity($request->except(['address', 'postal_code', 'subcategory', 'tags', 'quantity', 'price']));

        //Process to check address
        $activity = $this->checkAddress($activity, $request->input('address'), $request->input('postal_code.code'));

        // Create relationships
        $activity_with_relations = $this->createRelations($activity, $request);

        if (!$activity_with_relations) {
          return response([
            "error" => true,
            "messages" => ["Une erreur est survenue lors de la création des relations."]
          ]);
        } else {
          $activity = $activity_with_relations;
        }

        if ($activity->save()) {
          return response([
            "error" => false,
            "message" => "Activité créée.",
            "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags')->load('prices')->load('prices.quantity'),
          ]);
        } else {
          return response([
            "error" => true,
            "messages" => ["Une erreur est survenue lors de la création de l'activité."]
          ]);
        }
      }
    }
  }

  /**
   * Update an activity
   */
  public function update(Request $request, $id)
  {

    // Validate parameters
    $validator = Validator::make($request->all(), [
      'name' => 'string|max:55',
      'address' => 'string|max:55',
      'phone_number' => 'size:10',
      'opening_hours' => 'digits_between:4,5',
      'closing_hours' => 'digits_between:4,5',
      'average_time_spent' => 'digits_between:4,5',
      'disabled_access' => 'boolean',
      'subcategory.id' => 'exists:subcategories,id',
      'tags.*.id' => 'exists:tags,id',
      'postal_code.id' => 'exists:postal_code,id',
      'state.id' => 'exists:states,id',
      'quantity.id' => 'exists:quantities,id',
      'price.amount' => [
        'required',
        'regex:/^\d+(\.\d{1,2})?$/'
      ]
    ]);

    // Send errors if the validator failed
    if ($validator->fails()) {
      return response([
        "error" => true,
        "messages" => $validator->messages()
      ]);
    } else {
      // Check if the Activity exists
      $activity = Activity::find($id);
      if (Activity::find($id)) {
        // Not updating relationships here
        // Escaping address, city and postal_code because it implies another process to check adresse which is done later
        $activity->update($request->except(['address', 'postal_code', 'subcategory', 'tags']));
      }
      // Process to check address
      if ($request->has(['address', 'postal_code'])) {
        $activity = $this->checkAddress($activity, $request->input('address'), $request->input('postal_code.code'));
      }

      // Update relationships
      $activity_with_relations = $this->updateRelations($activity, $request);
      if (!$activity_with_relations) {
        return response([
          "error" => true,
          "messages" => ["Une erreur est survenue lors de la mise à jour des relations."]
        ]);
      } else {
        $activity = $activity_with_relations;
      }

      if ($activity->save()) {
        return response([
          "error" => false,
          "message" => "Activité mise à jour.",
          "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags')->load('prices')->load('prices.quantity'),
        ]);
      } else {
        return response([
          "error" => true,
          "messages" => ["Une erreur est survenue lors de la création de l'activité."]
        ]);
      }
    }
  }

  /**
   * ACTIVITY STATE MANAGEMENT
   * Pending -> Accept
   * Accept -> Pending
   * Pending -> Denied
   * Denied -> Pending
   */

  /**
   * Accept an activity
   */
  public function accept($id)
  {
    return $this->changeState($id, 'Pending', 'Accepted', 'En cours');
  }

  /**
   * Refuse an activity
   */
  public function deny($id)
  {
    return $this->changeState($id, 'Pending', 'Denied', 'En cours');
  }

  /**
   * Change the state of an activity to Pending
   */

  public function pend($id)
  {
    $activity = Activity::find($id);
    if ($activity) {
      $activity->state_id = State::where('label', 'Pending')->first()->id;
      $activity->save();
      return response([
        "error" => false,
        "message" => "État modifié.",
        "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags')->load('prices')->load('prices.quantity'),
      ]);
    } else {
      return response([
        'error' => true,
        'messages' => ["L'activité demandé n'existe pas"]
      ]);
    }
  }

  /**
   * LOGICAL FUNCTIONS
   */

  /**
   * Change the state of an activity
   */
  private function changeState($id, $check_state_label, $new_state_label, $error_msg)
  {
    $state_id = State::where('label', $check_state_label)->first()->id;
    $activity = Activity::find($id);

    if ($activity) {
      if ($activity->state->id == $state_id) {
        $activity->state_id = State::where('label', $new_state_label)->first()->id;
        $activity->save();
        return response([
          "error" => false,
          "message" => "État modifié.",
          "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags')->load('prices')->load('prices.quantity'),
        ]);
      } else {
        return response([
          "error" => true,
          "messages" => ["Vous ne pouvez approuver uniquement une activité qui est à l'état '" . $error_msg . "'."]
        ]);
      }
    } else {
      return response([
        'error' => true,
        'messages' => ["L'activité demandé n'existe pas"]
      ]);
    }
  }



  /**
   * Check the validity of an Address
   */
  private function checkAddress($activity, $request_addr, $request_pcode)
  {
    // Get the url from the constant file
    $url = Config::get('constants.API.Adress');
    // Prepare the parameters (Doc: https://geo.api.gouv.fr/adresse)
    $parameters = str_replace(' ', '+', $request_addr) . "&postcode=" . $request_pcode . "&limit=1";
    // Get the reponse in json
    $response = Http::get($url . "?q=" . $parameters)->json();
    // If the address exists
    if (!empty($response['features'])) {
      $properties =  $response['features'][0]['properties'];
      $coordinates = $response['features'][0]['geometry']['coordinates'];

      // Check if postal code exists in the database
      $postal_code = Postal_code::where('code', $properties['postcode'])->first();
      if ($postal_code) {
        $activity->address = $properties['name'];
        $activity->postal_code_id = $postal_code->id;
        $activity->longitude = $coordinates[0];
        $activity->latitude = $coordinates[1];
        return $activity;
      } else {
        return response([
          "error" => true,
          "messages" => ["L'adresse saisie n'existe pas."]
        ]);
      }
    } else {
      return response([
        "error" => true,
        "messages" => ["L'adresse saisie n'existe pas."]
      ]);
    }
  }

  /**
   * Create relationships
   */
  private function createRelations($activity, $request)
  {
    // Create state relation
    $administrator = Auth::user()->isAdministrator();
    if ($administrator) {
      $activity->state_id = $request->input('state.id');
    } else {
      $activity->state_id = State::where('label', 'Pending')->first()->id;
    }
    // Create subcategory relation
    $activity->subcategory_id = $request->input('subcategory.id');

    // We are saving here because to save an Activity we need a state_id and a subcategory_id
    if (!$activity->save()) return false;

    // Create professional relation
    $activity->professional_id = $request->input('professional.id');

    if (!$activity->save()) return false;

    $price = $this->createPrice($request->input('price.amount'), $activity->id, $request->input('quantity.id'));
    if (!$price) return false;

    // Create tags relation
    $tags = $request->input('tags');
    $ids = [];
    // Iterate through each tag
    foreach ($tags as $tag) {
      // Get the Tag's id
      array_push($ids, $tag['id']);
    }
    // Erase and create new relation(s) with the tag(s) given in the request body
    $activity->tags()->sync($ids);

    return ($activity->save()) ? $activity : false;
  }

  /**
   * Updates relationships
   */
  private function updateRelations($activity, $request)
  {
    // Update state
    if ($request->has('state')) {
      $activity->state_id = $request->input('state.id');
      if (!$activity->save()) return false;
    }

    // Update sub
    if ($request->has('subcategory')) {
      $activity->subcategory_id = $request->input('subcategory.id');
      if (!$activity->save()) return false;
    }

    //Update price
    if ($request->has('quantity') && $request->has('price')) {

      // Delete old price
      foreach ($activity->prices() as $price) {
        $price->delete();
      }

      // Create new price
      $price = $this->createPrice($request->input('price.amount'), $activity->id, $request->input('quantity.id'));
      if (!$price) return false;
    }


    // Update tags
    if ($request->has('tags')) {
      $tags = $request->input('tags');
      $ids = [];
      // Iterate through each tag
      foreach ($tags as $tag) {
        // Get the Tag's id
        array_push($ids, $tag['id']);
      }
      // Erase and create new relation(s) with the tag(s) given in the request body
      $activity->tags()->sync($ids);
    }

    return ($activity->save()) ? $activity : false;
  }

  private function createPrice($amount, $activity_id, $quantity_id)
  {
    $price = new Price([
      'amount' => $amount
    ]);
    $price->activity_id = $activity_id;
    $price->quantity_id = $quantity_id;
    return ($price->save()) ? $price : false;
  }
}
