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
  public function jardins()
  {
    $subcategory_id = Subcategory::where('label', 'Jardin')->first()->id;
    $state_id = State::where('label', 'Accepted')->first()->id;
    $quantity_id = Quantity::where('label', '1 personne')->first()->id;

    $filename = storage_path('/app/espaces_verts.csv');
    $row = 1;
    if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        $num = count($data);
        // echo "<p> $num fields in line $row: <br /></p>\n";
        // $row++;
        if (count($data) > 2) {
          if (($data[3] != "Cimetière") && ($data[3] != "Talus")) {
            if ($this->getSuperficie($data[10]) >= 300) {
              $name = $data[1];
              // Set openning and closing hours from 8h00 -> 19h00 by default
              $opening_hours = 28800;
              $closing_hours = 68400;

              // If the garden is open 24h/24 set from 00:00:01 to 23h59
              if ($data[19] == "Oui") {
                $opening_hours = 60;
                $closing_hours = 86340;
              }
              $address = $data[4] . " " . $data[6] . " " . $data[7];
              $postal_code = $data[8];

              $check_activity = Activity::where('name', $name)->get();

              if (count($check_activity) == 0) {
                $activity = new Activity();
                $activity = $this->checkAddress($activity, $address, $postal_code);
                if ($activity) {
                  $activity->name = $name;
                  $activity->siren = "000000000";
                  $activity->phone_number = "0000000000";
                  $activity->opening_hours = $opening_hours;
                  $activity->closing_hours = $closing_hours;
                  $activity->average_time_spent = 3600;
                  $activity->subcategory_id = $subcategory_id;
                  $activity->state_id = $state_id;
                  $activity->disabled_access = false;
                  if ($activity->save()) {
                    $new_price = new Price([
                      'amount' => 00.00
                    ]);

                    $new_price->activity_id = $activity->id;
                    $new_price->quantity_id = $quantity_id;
                    $new_price->save();
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
