<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use App\State;
use App\Subcategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use stdClass;

class SearchController extends Controller
{
  /**
   * Return the complete journey in between the activities randomely selected
   */
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
      'amount' => [
        'required',
        'regex:/^\d+(\.\d{1,2})?$/'
      ],
      'transport.id' => 'exists:transports,id|required',
      'tags.*.id' => 'exists:tags,id|required',
      'subcategories.*.id' => 'exists:subcategories,id|required'
    ]);

    // Send errors if the validator failed
    if ($validator->fails()) {
      return response([
        'error' => true,
        'messages' => $validator->messages()
      ]);
    } else {
      // Check in which area is the user
      $departure = $this->getAddress($request->input('position.longitude'), $request->input('position.latitude'));
      $postal_code = $departure->postal_code;
      if ($postal_code && $departure->name) {
        $postal_code_id = Postal_code::where('code', $postal_code)->first()->id;
        $subcategories = $request->input('subcategories.*.id');
        $duration = $request->input('duration');
        $amount = $request->input('amount');
        $tags = $request->input('tags.*.id');
        $state_id = State::where('label', 'Accepted')->first()->id;
        // Get all the activities according the filters
        $activities = $this->getActivitiesByFilters($state_id, $postal_code_id, $subcategories, $amount, $tags);

        // Filter the activities according the time the user has
        $activities = $this->filterAll($activities, $duration, $this->checkRestauration($subcategories), $amount);

        $journeys = [];
        if ($activities && (count($activities) > 0)) {
          // Generate journeys
          $journeys = $this->generateJourneys($departure, $activities, $request->input('transport.label'));
        }

        if ($journeys) {
          return response([
            'error' => false,
            'journeys' => $journeys
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
      $properties = $response['features'][0]['properties'];
      $departure = new stdClass();
      $departure->name = $properties['name'];
      $departure->latitude =  $latitude;
      $departure->longitude = $longitude;
      $departure->postal_code = $properties['postcode'];
      return $departure;
    } else {
      return false;
    }
  }

  /**
   * Get activities according the filters
   */
  private function getActivitiesByFilters($state_id, $postal_code_id, $subcategories, $amount, $tags)
  {
    $activities = Activity::where('state_id', $state_id)
      ->where('postal_code_id', $postal_code_id)
      ->whereHas('subcategory', function ($query) use ($subcategories) {
        return $query->whereIn('subcategories.id', $subcategories);
      })
      ->whereHas('prices', function ($query) use ($amount) {
        return $query->where('amount', '<=', $amount);
      });
    $activities_no_restaurant = $activities->get();
    // Include tags only if it's a restaurant
    if ($this->checkRestauration($subcategories)) {
      $activities->whereHas('tags', function ($q) use ($tags) {
        $q->whereIn('tags.id', $tags);
      })->get();
    }
    return $activities_no_restaurant->merge($activities);
  }

  /**
   * Check if the user wants to eat
   */
  private function checkRestauration($subcategories)
  {
    foreach ($subcategories as $subcategory) {
      $subcategory = Subcategory::find($subcategory);
      if ($subcategory->isRestauration()) return true;
    }
    return false;
  }

  /**
   * Filter activity by time
   */
  private function filterAll($activities, $duration, $restaurant, $amount_max)
  {
    $sum = 0;
    $i = 0;
    $amount = 0;
    // Shuffle the activites
    $tmp_activities = $activities->shuffle();
    $activities = collect();

    // Get the current time in secondes
    $now = $this->convertTimeToSecond(Carbon::now()->toTimeString());

    // Check if the user asked for a restaurant
    if ($restaurant) {
      // Get a random restaurant
      $activity = $this->getRandomlyRestaurant($tmp_activities, $amount_max);
      // Add the restaurant in the first position of the activites array
      if ($activity) {
        $sum = $activity->average_time_spent;
        $amount = $activity->prices()->first()->amount;
        $activities->push($activity);

        // Remove all the activities which has the Restauration category from the activites array, so it cannot be chosen again
        // This is because for the MVP we decided to put only one activity of Restauration type in the output
        $tmp_activities = $this->removeRestaurants($tmp_activities);
      } else {
        Log::error('No activity with the restauration category !');
        return false;
      }
    }

    // Add all the other activities according the time left for the user.
    while (($sum < $duration) && ($i < count($tmp_activities))) {
      $current_activity = $tmp_activities[$i];
      // Check if the activity is open AND if it's not closed and won't be after spending time in the previous activities AND the amount of the activity added to previous ones won't exceed the user's max
      if (($now > $current_activity->opening_hours) && ($now < $current_activity->closing_hours + $sum) && ($amount_max >= $current_activity->prices()->first()->amount + $amount)) {
        // Add the average time spent in a activity to the sum
        $amount += $current_activity->prices()->first()->amount;
        $sum += $current_activity->average_time_spent;
        $activities->push($current_activity);
      }
      // Limit the number of activites
      if (count($activities) == 3) return $activities;
      $i++;
    }
    return $activities;
  }

  /**
   * Get a random activity with the Restauration category
   */
  private function getRandomlyRestaurant($activities, $amount_max)
  {
    // Get current time in secondes
    $now = $this->convertTimeToSecond(Carbon::now()->toTimeString());

    // Iterate through each activity
    foreach ($activities as $activity) {
      // If the activiy is a Restauration category AND it's open AND not closed AND the amount is less than the user's max
      if (($activity->subcategory->isRestauration()) && ($activity->opening_hours < $now) && ($activity->closing_hours > $now) && ($activity->prices()->first()->amount <= $amount_max)) {
        return $activity;
      }
    }
    Log::error('No activity with the restauration category !');
    return false;
  }

  /**
   * Remove all restaurants from activity
   */
  private function removeRestaurants($activities)
  {
    $tmp_activities = collect();

    // Iterate through each activity
    foreach ($activities as $activity) {
      // If it's not a restaurant
      if (!($activity->subcategory->isRestauration())) {
        $tmp_activities->push($activity);
      }
    }
    return $tmp_activities;
  }

  /**
   * Convert a TimeString to secondes
   */
  private function convertTimeToSecond(string $time): int
  {
    $d = explode(':', $time);
    return ($d[0] * 3600) + ($d[1] * 60) + $d[2];
  }

  /**
   * Generate Journeys
   */
  private function generateJourneys($departure, $activities, $mode)
  {
    $journeys = [];
    // Get journey from the departure to the first activity
    array_push($journeys, $this->getJourney($departure, $activities[0], $mode));
    // Get the journey in between all the other activities and check if there is more than one activity
    if (count($activities) > 1) {
      for ($i = 0; $i < count($activities); $i++) {
        // Check if there is an activity left
        if ($i + 1 < count($activities)) {
          $journey = $this->getJourney($activities[$i], $activities[$i + 1], $mode);
          // If a journey exists in between the two activites
          if ($journey) {
            array_push($journeys, $journey);
          } else {
            Log::error('No journey in between ' . $activities[$i]->id . ' and ' . $activities[$i + 1]->id);
            return null;
          }
        }
      }
    }
    return $journeys;
  }

  /**
   * Get the journey from an activity to another one
   */
  private function getJourney($from, $to, $mode)
  {
    // Get the url from the constant file
    $url = Config::get('constants.API.Navitia');

    // Call API
    $response = Http::withHeaders([
      'Authorization' => env('NAVITIA_KEY')
    ])->get($url . '?from=' . $from->longitude . ';' . $from->latitude . '&to=' . $to->longitude . ';' . $to->latitude);

    $journeys = $response['journeys'];

    // If the user wants to only walk
    if ($mode == 'Marche') {
      $journeys = $this->filterWalkingJourneys($journeys);
    }
    // If there are journeys in between the two points
    if ($journeys > 0) {
      $tmp_journey = $journeys[0];
      $tmp_sections = [];

      //Clean sections to get only the important informations
      foreach ($tmp_journey['sections'] as $section) {
        if ($section['from']) {
          $new_section = new stdClass();
          $new_section->duration =  $section['duration'];
          $tmp_from = $this->getSectionInformations($section['from'], $section['from']['embedded_type']);
          $tmp_to = $this->getSectionInformations($section['to'], $section['to']['embedded_type']);
          if (!$tmp_from || !$tmp_to) {
            Log::warning("Coulnd't get section informations in between activity:" . $from->id . ' and activity:' . $to->id);
            return null;
          }
          $new_section->from = $tmp_from;
          $new_section->to = $tmp_to;
          $new_section->type = array_key_exists('type', $section) ? $section['type'] : null;

          // Get public transport informations
          if ($new_section->type == "public_transport") {
            $new_section->subway_information = new stdClass();
            $new_section->subway_information->direction = $section['display_informations']['direction'];
            $new_section->subway_information->line = $section['display_informations']['label'];
            $new_section->subway_information->mode = $section['display_informations']['physical_mode'];
          }
          array_push($tmp_sections, $new_section);
        }
      }

      $journey = new stdClass();
      $journey->from = $from->name;
      $journey->to = $to->name;
      $journey->duration = $tmp_journey['duration'];
      $journey->sections = $tmp_sections;

      return $journey;
    } else {
      Log::info('No journeys');
      return null;
    }
  }

  /**
   * Get section usefull informations
   */
  private function getSectionInformations($section, $type)
  {
    $information = null;
    if ($type == "address") {
      $information = new stdClass();
      $information->name = $section['address']['name'];
      $information->latitude = $section['address']['coord']['lat'];
      $information->longitude = $section['address']['coord']['lon'];
    } elseif ($type == "stop_point") {
      $information = new stdClass();
      $information->name = $section['stop_point']['name'];
      $information->latitude = $section['stop_point']['coord']['lat'];
      $information->longitude = $section['stop_point']['coord']['lon'];
    }
    return $information;
  }

  /**
   * Filter the journeys array to get only the walking journeys
   */
  private function filterWalkingJourneys($journeys)
  {
    $tmp_journeys = [];
    foreach ($journeys as $journey) {
      if ($journey['type'] == "non_pt_walk") {
        array_push($tmp_journeys, $journey);
      }
    }
    return $tmp_journeys;
  }
}
