<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Subcategory;
use App\Tag;
use Illuminate\Http\Request;
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
    return Activity::with('subcategory')->with('professional')->with('tags')->find($id);
  }

  public function update(Request $request, $id)
  {

    // Validate parameters
    $validator = Validator::make($request->all(), [
      'name' => 'string|max:55',
      'address' => 'string|max:55',
      'city' => 'string|max:55',
      'postal_code' => 'size:5',
      'phone_number' => 'size:10',
      'opening_hours' => 'digits_between:4,5',
      'closing_hours' => 'digits_between:4,5',
      'average_time_spent' => 'digits_between:4,5',
      'disabled_access' => 'boolean',
      'subcategory.id' => 'exists:subcategories,id',
      'tags.*.id' => 'exists:tags,id'
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
      if (Activity::find($id)){
        // Not updating relationships here
        // Escaping address, city and postal_code because it implies another process to check adresse which is done later
        $activity->update($request->except(['address', 'city', 'postal_code','subcategory', 'tags']));
      }
      // Process to check address
      if ($request->has(['address', 'city', 'postal_code'])) {
        // Get the url from the constant file
        $url = Config::get('constants.API.Adress');
        // Prepare the parameters (Doc: https://geo.api.gouv.fr/adresse)
        $parameters = str_replace(' ', '+', $request->input('address'))."&postcode=".$request->input('postal_code')."&limit=1";
        // Get the reponse in json
        $response = Http::get($url."?q=".$parameters)->json();
        // If the address exists
        if (!empty($response['features'])) {
          $properties =  $response['features'][0]['properties'];
          $coordinates = $response['features'][0]['geometry']['coordinates'];
          $activity->address = $properties['name'];
          $activity->city = $properties['city'];
          $activity->postal_code = $properties['postcode'];
          $activity->longitude = $coordinates[0];
          $activity->latitude = $coordinates[1];
          $activity->save();
        } else {
          return response([
            "error" => true,
            "messages" => ["L'adresse saisie n'existe pas."]
          ]);
        }
      }
      // If the user wants to update the subcategory
      if ($request->has('subcategory')) {
        $subcategory_id = $request->input('subcategory.id');
        // Check (again) if the Subcategory exists
        if(Subcategory::find($subcategory_id)){
          // Update and save
          $activity->subcategory_id = $subcategory_id;
          $activity->save();
        } else {
          return response([
            "error" => true,
            "messages" => ["La Sous-catégorie saisie n'existe pas."]
          ]);
        }
      }
      // Update the tags
      if ($request->has('tags')) {
        $tags = $request->input('tags');
        $ids = [];
        // Iterate through each tag
        foreach ($tags as $tag) {
          // Check (again) if the Tag exists
          if (Tag::find($tag['id'])) {
            // Get the Tag's id
            array_push($ids, $tag['id']);
          } else {
            return response([
              "error" => true,
              "messages" => ["Le Tag saisie n'existe pas."]
            ]);
          }

        }
        // Erase and create new relation(s) with the tag(s) given in the request body
        $activity->tags()->sync($ids);
      }

      return response([
        "error" => false,
        "message" => "Activity updated.",
        "activity" => Activity::with('subcategory')->with('professional')->with('tags')->find($id)
      ]);
    }
  }
}
