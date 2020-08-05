<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use App\Price;
use App\Quantity;
use App\State;
use App\Subcategory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{

  public function museums()
  {
    $subcategory_id = Subcategory::where('label', 'Musée')->first()->id;
    $state_id = State::where('label', 'Accepted')->first()->id;
    $quantity_id = Quantity::where('label', '1 personne')->first()->id;

    $filename = storage_path('/app/musees.csv');
    if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if (count($data) > 2) {
          if ($data[8] === "PARIS") {

            // Handle special caracters
            $name = utf8_encode($data[5]);
            $name = str_replace("", "'", $name);
            // Set openning and closing hours from 10h00 -> 18h00 by default
            $opening_hours = 36000;
            $closing_hours = 64800;
            $average_time_spent = 3600;

            $address = $data[6];
            $postal_code = $data[7];

            $disabled_access = false;
            $amount = 00.00;

            // Check if museum exists already
            $check_activity = Activity::where('name', $name)->get();

            if (count($check_activity) == 0) {
              $activity = new Activity();
              // Convert INSEE code to postal code if necessary
              $postal_code = substr_replace($postal_code, "0", 2, 1);
              $activity = $this->checkAddress($activity, $address, $postal_code);
              // Create the activity and the price
              if ($activity) {
                $activity = $this->createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $state_id, $disabled_access);
                if ($activity->save()) {
                  $this->createPrice($activity, $amount, $quantity_id);
                }
              }
            }
          }
        }
      }
    }
  }

  public function jardins()
  {
    $subcategory_id = Subcategory::where('label', 'Jardin')->first()->id;
    $state_id = State::where('label', 'Accepted')->first()->id;
    $quantity_id = Quantity::where('label', '1 personne')->first()->id;

    $filename = storage_path('/app/espaces_verts.csv');
    if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Check if it's a complete line
        if (count($data) > 2) {
          if (($data[3] != "Cimetière") && ($data[3] != "Talus")) {
            if ($this->getSuperficie($data[10]) >= 300) {
              $name = $data[1];
              // Set openning and closing hours from 8h00 -> 19h00 by default
              $opening_hours = 28800;
              $closing_hours = 68400;
              $average_time_spent = 3600;

              // If the garden is open 24h/24 set from 00:00:01 to 23h59
              if ($data[19] == "Oui") {
                $opening_hours = 60;
                $closing_hours = 86340;
              }
              $address = $data[4] . " " . $data[6] . " " . $data[7];
              $postal_code = $data[8];

              $disabled_access = false;
              $amount = 00.00;

              // Check if the jardin already exists
              $check_activity = Activity::where('name', $name)->get();

              if (count($check_activity) == 0) {
                $activity = new Activity();
                $activity = $this->checkAddress($activity, $address, $postal_code);
                // Create the activity and the price
                if ($activity) {
                  $activity = $this->createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $state_id, $disabled_access);
                  if ($activity->save()) {
                    $this->createPrice($activity, $amount, $quantity_id);
                  }
                }
              }
            }
          }
        }
      }
      fclose($handle);
    }
  }

  /**
   * Create Activity
   */
  private function createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $state_id, $disabled_access)
  {
    $activity->name = $name;
    $activity->siren = "000000000";
    $activity->phone_number = "0000000000";
    $activity->opening_hours = $opening_hours;
    $activity->closing_hours = $closing_hours;
    $activity->average_time_spent = $average_time_spent;
    $activity->subcategory_id = $subcategory_id;
    $activity->state_id = $state_id;
    $activity->disabled_access = $disabled_access;
    return $activity;
  }

  /**
   * Create Price
   */
  private function createPrice($activity, $amount, $quantity_id)
  {
    $new_price = new Price([
      'amount' => $amount
    ]);

    $new_price->activity_id = $activity->id;
    $new_price->quantity_id = $quantity_id;
    $new_price->save();
  }

  /**
   * Get the superficie
   */
  private function getSuperficie($superficie)
  {
    $arr = explode("m", $superficie);
    $superficie = str_replace(' ', '', $arr[0]);
    return (int)$superficie;
  }

  /**
   * Check the validity of an Address
   */
  private function checkAddress($activity, $address, $postal_code)
  {
    // Get the url from the constant file
    $url = Config::get('constants.API.Adresse.search');
    // Prepare the parameters (Doc: https://geo.api.gouv.fr/adresse)
    $parameters = str_replace(' ', '+', $address) . "&postcode=" . $postal_code . "&limit=1";
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
        return null;
      }
    } else {
      return null;
    }
  }
}
