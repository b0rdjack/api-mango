<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
  public function search(Request $request)
  {

    // Validate parameters
    $validator = Validator::make($request->all(), [
      'position.longitude' => [
        'required',
        'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'
      ],
      'position.latitude' => [
        'required',
        'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'
      ],
      'duration' => 'digits_between:4,5|required',
      'restaurant' => 'boolean|required',
      'amount' => [
        'required',
        'regex:/^\d+(\.\d{1,2})?$/'
      ],
      'transport.id' => 'exists:transports,id|required',
      'tags.*.id' => 'exists:tags,id|required'
    ]);

    // Send errors if the validator failed
    if ($validator->fails()) {
      return response([
        'error' => true,
        'messages' => $validator->messages()
      ]);
    } else {

      // Check in which area is the user
      $postal_code = $this->getAddress($request->input('position.longitude'), $request->input('position.latitude'));
      if ($postal_code) {
        $postal_code_id = Postal_code::where('code', $postal_code)->first()->id;

        $duration = $request->input('duration');
        $amount = $request->input('amount');
        $tags = $request->input('tags.*.id');

        // Get all the activities according the filters
        $activities = $this->getActivities($postal_code_id, $amount, $tags);

        // Filter the activities according the time the user has
        $activities = $this->filterByTime($activities, $duration, $request->input('restaurant'));

        if ($activities) {
          return response([
            'error' => false,
            'activites' => $activities
          ]);
        } else {
          return response([
            'error' => true,
            'messages' => ["Il n'y a aucun parcours correspondant à vos critères pour le moment :("]
          ]);
        }
      } else {
        return response([
          'error' => true,
          'messages' => ['La posistion saisie ne correpond à aucune adresse.']
        ]);
      }
    }
  }

  /**
   * Get address from longitude and latitude
   */
  private function getAddress($longitude, $latitude)
  {
    // Get the url from the constant file
    $url = Config::get('constants.API.Adresse.reverse');
    $response = Http::get($url . '?lon=' . $longitude . '&lat=' . $latitude);
    // If the address exists
    if (!empty($response['features'])) {
      return $response['features'][0]['properties']['postcode'];
    } else {
      return false;
    }
  }

  /**
   * Get activities according the filters
   */
  private function getActivities($postal_code_id, $amount, $tags)
  {
    return  Activity::where('postal_code_id', $postal_code_id)
      ->whereHas('prices', function ($query) use ($amount) {
        return $query->where('amount', '<=', $amount);
      })
      ->whereHas('tags', function ($q) use ($tags) {
        $q->whereIn('tags.id', $tags);
      })->get();
  }

  /**
   * Filter activity by time
   */
  private function filterByTime($activities, $duration, $restaurant)
  {
    $sum = 0;
    $i = 0;

    // Shuffle the activites
    $tmp_activities = $activities->shuffle();
    $activities = collect();

    // Get the current time in secondes
    $now = $this->convertTimeToSecond(Carbon::now()->toTimeString());

    // Check if the user asked for a restaurant
    if ($restaurant) {

      // Get a random restaurant
      $activity = $this->getRandomlyRestaurant($tmp_activities);
      // Add the restaurant in the first position of the activites array
      if ($activity) {
        $sum = $activity->average_time_spent;
        $activities->push($activity);
      } else {

        return false;
      }
    }

    // Add all the other activities according the time left for the user.
    while (($sum < $duration) && ($i < count($tmp_activities))) {
      $current_activity = $tmp_activities[$i];
      // Check if the activity is open AND if it's not closed and won't be after spending time in the previous activities
      if (($now > $current_activity->opening_hours) && ($now < $current_activity->closing_hours + $sum)) {
        // Add the average time spent in a activity to the sum
        $sum += $current_activity->average_time_spent;
        $activities->push($current_activity);
      }
      $i++;
    }
    return $activities;
  }

  /**
   * Get a random restaurant
   */
  private function getRandomlyRestaurant($activities)
  {
    // Shuffle activites array
    $activities = $activities->shuffle();
    // Get current time in secondes
    $now = $this->convertTimeToSecond(Carbon::now()->toTimeString());

    // Iterated through each activity
    foreach ($activities as $activity) {
      // If the activiy is a restaurant AND it's open AND not closed
      if (($activity->restaurant()->exists()) && ($activity->opening_hours < $now) && ($activity->closing_hours > $now)) {
        return $activity;
      }
    }
    return false;
  }

  // Convert a TimeString to secondes
  private function convertTimeToSecond(string $time): int
  {
    $d = explode(':', $time);
    return ($d[0] * 3600) + ($d[1] * 60) + $d[2];
  }
}
