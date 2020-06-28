<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use App\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ActivityController extends Controller
{

  public function index()
  {
    return Activity::with('subcategory')->with('professional')->with('tags')->get();
  }

  public function show($id)
  {
    $activity = Activity::with('postal_code')->with('subcategory')->with('professional')->with('tags')->find($id);
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
      'state.id' => 'exists:states,id'
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
        $activity = new Activity($request->except(['address', 'postal_code', 'subcategory', 'tags']));

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
            "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags'),
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
      'state.id' => 'exists:states,id'
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
          "activity" => $activity->load('state')->load('postal_code')->load('subcategory')->load('professional')->load('tags'),
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

    // Create professional relation
    $activity->professional_id = $request->input('professional.id');

    if (!$activity->save()) return false;

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

    if ($activity->save()) {
      return $activity;
    } else {
      return false;
    }
  }

  /**
   * Updates relationships
   */
  private function updateRelations($activity, $request)
  {
    // Update state
    if ($request->has('state')) {
      $activity->state_id = $request->input('state.id');
    }

    // Update sub
    if ($request->has('subcategory')) {
      $activity->subcategory_id = $request->input('subcategory.id');
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
    if ($activity->save()) {
      return $activity;
    } else {
      return false;
    }
  }
}
