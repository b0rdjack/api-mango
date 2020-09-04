<?php

namespace App\Http\Controllers;

use App\Activity;
use App\Postal_code;
use App\Price;
use App\Quantity;
use App\Restaurant;
use App\State;
use App\Subcategory;
use App\Tag;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
  private $state_id;
  private $quantity_id;

  public function __construct()
  {
    $this->state_id =  State::where('label', 'Accepted')->first()->id;
    $this->quantity_id = Quantity::where('label', '1 personne')->first()->id;
  }

  public function restaurants()
  {
    $filename = storage_path('/app/restaurants.csv');

    // 11h -> 14h / 19h00 -> 23h00 => RESTAURANTS
    $lunch_opening_hours = 39600;
    $lunch_closing_hours = 50400;
    $dinner_opening_hours = 68400;
    $dinner_closing_hours = 82800;

    // 11h -> 23h59 => FAST FOOD
    $ff_opening_hours = 39600;
    $ff_closing_hours = 86340;

    // 10h -> 21h00 => OTHERS (Coffee etc.)
    $other_opening_hours = 36000;
    $other_closing_hours = 75600;

    // TAGS
    $african_tag = Tag::where("label", "Africain")->first()->id;
    $asian_tag = Tag::where("label", "Asiatique")->first()->id;
    $french_tag = Tag::where("label", "Français")->first()->id;
    $fast_food_tag = Tag::where("label", "Fast Food")->first()->id;
    $greek_tag = Tag::where("label", "Grec")->first()->id;
    $indian_tag = Tag::where("label", "Indien")->first()->id;
    $italian_tag = Tag::where("label", "Italien")->first()->id;
    $japanese_tag = Tag::where("label", "Japonais")->first()->id;
    $mexican_tag = Tag::where("label", "Mexicain")->first()->id;
    $middle_east_tag = Tag::where("label", "Moyen-Orient")->first()->id;
    $turkish_tag = Tag::where("label", "Turque")->first()->id;
    $vegetarian_tag = Tag::where("label", "Végétarien")->first()->id;
    $vietnamese_tag = Tag::where("label", "Vietnamien")->first()->id;

    // SUB CATEGORIES
    $bar_id = Subcategory::where('label', 'Bar')->first()->id;
    $cafe_id = Subcategory::where('label', 'Café')->first()->id;
    $salon_the_id = Subcategory::where('label', 'Salon de thé')->first()->id;
    $restaurant_id = Subcategory::where('label', 'Restaurant')->first()->id;
    $fast_food_id = Subcategory::where('label', 'Fast Food')->first()->id;

    if (($handle = fopen($filename, 'r')) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        // Check if name exists and its valid
        if (!empty($data[6]) && (preg_match("/[a-z]/i", $data[6]))) {
          // If the subcategory is not empty
          if (!empty($data[9])) {
            // Check if there is a lat and a long value
            if ((!empty($data[3])) && (!empty($data[4]))) {
              $latitude = $data[3];
              $longitude = $data[4];
              // Are the values correct ? (Not letters)
              if ((preg_match("/[a-z]/i", $latitude) == 0) && (preg_match("/[a-z]/i", $longitude) == 0)) {

                $disabled_access = false;
                $amount = $this->getPrice($data[12]);
                $name = $data[6];

                // 1h30
                $average_time_spent = 5400;
                $activity = new Activity();

                $activity = $this->getAddress($activity, $longitude, $latitude);
                $data[9] = strval($data[9]);

                // Check if address is exisiting
                if ($activity) {
                  // Bar
                  if ((strpos($data[9], "Bar") !== FALSE)
                    || (strpos($data[9], "Cocktail Bar") !== FALSE)
                    || (strpos($data[9], "Pub") !== FALSE)
                    || (strpos($data[9], "Hotel Bar") !== FALSE)
                  ) {
                    $activity = $this->createActivity($activity, $name, $other_opening_hours, $other_closing_hours, $average_time_spent, $bar_id, $this->state_id, $disabled_access);
                    if ($activity->save()) {
                      $this->createPrice($activity, $amount, $this->quantity_id);
                    }
                  }
                  // Café
                  if ((strpos($data[9], "Café") !== FALSE)
                    || (strpos($data[9], "Coffee Shop") !== FALSE)
                    || (strpos($data[9], "Cafetaria") !== FALSE)
                  ) {
                    $activity = $this->createActivity($activity, $name, $other_opening_hours, $other_closing_hours, $average_time_spent, $cafe_id, $this->state_id, $disabled_access);
                    if ($activity->save()) {
                      $this->createPrice($activity, $amount, $this->quantity_id);
                    }
                  }
                  // Salon de thé
                  if (strpos($data[9], "Tea Room") !== FALSE) {
                    $activity = $this->createActivity($activity, $name, $other_opening_hours, $other_closing_hours, $average_time_spent, $salon_the_id, $this->state_id, $disabled_access);
                    if ($activity->save()) {
                      $this->createPrice($activity, $amount, $this->quantity_id);
                    }
                  }
                  // Fast Food
                  if (strpos($data[9], "Pizza Place") !== FALSE) {
                    $activity = $this->createActivity($activity, $name, $ff_opening_hours, $ff_closing_hours, $average_time_spent, $restaurant_id, $this->state_id, $disabled_access);
                    if ($activity->save()) {
                      $this->createPrice($activity, $amount, $this->quantity_id);
                    }
                  }
                  // Restaurants
                  if (strpos($data[9], "Restaurant") !== FALSE) {
                    $type = trim(explode("Restaurant", $data[9], 2)[0]);
                    $maximum_capacity = 10;
                    $activity = $this->createRestaurant(
                      $activity,
                      $name,
                      $lunch_opening_hours,
                      $lunch_closing_hours,
                      $dinner_opening_hours,
                      $dinner_closing_hours,
                      $average_time_spent,
                      $restaurant_id,
                      $disabled_access,
                      $amount,
                      $maximum_capacity
                    );
                    switch ($type) {
                      case "African":
                        $this->createTag($activity, $african_tag);
                        break;
                      case "Asian":
                        $this->createTag($activity, $asian_tag);
                        break;
                      case "Chinese":
                        $this->createTag($activity, $asian_tag);
                        break;
                      case "Fast Food":
                        $activity = $this->createActivity($activity, $name, $ff_opening_hours, $ff_closing_hours, $average_time_spent, $fast_food_id, $this->state_id, $disabled_access);
                        if ($activity->save()) {
                          $this->createPrice($activity, $amount, $this->quantity_id);
                        }
                        break;
                      case "French":
                        $this->createTag($activity, $french_tag);
                        break;
                      case "Greek":
                        $this->createTag($activity, $greek_tag);
                        break;
                      case "Indian":
                        $this->createTag($activity, $indian_tag);
                        break;
                      case "Italian":
                        $this->createTag($activity, $italian_tag);
                        break;
                      case "Japanese":
                        $this->createTag($activity, $japanese_tag);
                        break;
                      case "Mexican":
                        $this->createTag($activity, $mexican_tag);
                        break;
                      case "Middle Eastern":
                        $this->createTag($activity, $middle_east_tag);
                        break;
                      case "Sushi":
                        $this->createTag($activity, $japanese_tag);
                        break;
                      case "Turkish":
                        $this->createTag($activity, $turkish_tag);
                        break;
                      case "Vegetarian / Vegan":
                        $this->createTag($activity, $vegetarian_tag);
                        break;
                      case "Vietnamese":
                        $this->createTag($activity, $vietnamese_tag);
                        break;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  public function cinemas()
  {
    $subcategory_id = Subcategory::where('label', 'Cinéma')->first()->id;

    $filename = storage_path('/app/cinemas.csv');

    if (($handle = fopen($filename, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
        if ($data[1] == "75") {
          $name = $data[2];

          // Set opening and closing hours from 10h00 -> 22h00 by default
          $opening_hours = 36000;
          $closing_hours = 79200;
          $average_time_spent = 7200;

          $disabled_access = false;
          $amount = 00.00;

          // Address configuration
          $address = $data[4];

          $postal_code = $data[5];
          $postal_code = substr_replace($postal_code, "0", 2, 1);
          $postal_code = Postal_code::where('code', $postal_code)->first();

          $coordinates = $data[33];
          $coordinates = explode(",", $coordinates);
          $latitude = $coordinates[0];
          $longitude = $coordinates[1];

          // Check if museum exists already
          $check_activity = Activity::where('name', $name)->get();
          if (count($check_activity) == 0) {
            $activity = new Activity();

            $activity->address = $address;
            $activity->postal_code_id = $postal_code->id;
            $activity->longitude = $longitude;
            $activity->latitude = $latitude;

            $activity = $this->createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $this->state_id, $disabled_access);
            if ($activity->save()) {
              $this->createPrice($activity, $amount, $this->quantity_id);
            }
          }
        }
      }
    }
  }

  public function museums()
  {
    $subcategory_id = Subcategory::where('label', 'Musée')->first()->id;

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
                $activity = $this->createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $this->state_id, $disabled_access);
                if ($activity->save()) {
                  $this->createPrice($activity, $amount, $this->quantity_id);
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
                  $activity = $this->createActivity($activity, $name, $opening_hours, $closing_hours, $average_time_spent, $subcategory_id, $this->state_id, $disabled_access);
                  if ($activity->save()) {
                    $this->createPrice($activity, $amount, $this->quantity_id);
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
   * Create Restaurant
   */
  private function createRestaurant(
    $activity,
    $name,
    $lunch_opening_hours,
    $lunch_closing_hours,
    $dinner_opening_hours,
    $dinner_closing_hours,
    $average_time_spent,
    $subcategory_id,
    $disabled_access,
    $amount,
    $maximum_capacity
  ) {
    // Create Activity
    $activity = $this->createActivity($activity, $name, $lunch_opening_hours, $lunch_closing_hours, $average_time_spent, $subcategory_id, $this->state_id, $disabled_access);
    // Create Price
    if ($activity->save()) {
      $this->createPrice($activity, $amount, $this->quantity_id);
    }
    // Create dinner hours
    $restaurant = new Restaurant();
    $restaurant->opening_hours = $dinner_opening_hours;
    $restaurant->closing_hours = $dinner_closing_hours;
    $restaurant->activity_id = $activity->id;
    $restaurant->maximum_capacity = $maximum_capacity;
    $restaurant->save();

    $activity->save();
    return $activity;
  }

  /**
   * Create Tag
   */
  private function createTag($activity, $tag_id)
  {
    $activity->tags()->attach($tag_id);
    $activity->save();
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

  private function getAddress($activity, $longitude, $latitude)
  {
    // Get the url from the constant file
    $url = Config::get('constants.API.Adresse.reverse');
    // Prepare the parameters (Doc: https://geo.api.gouv.fr/reverse)
    $parameters = $url . "?lon=" . $longitude . "&lat=" . $latitude;
    // Get the reponse in json
    $response = Http::get($parameters)->json();
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

  /**
   * Get the average amount from price indication, $, $$ - $$$, $$$$
   */
  private function getPrice($price_indication)
  {
    $price_indication = substr_count($price_indication, "$");
    switch ($price_indication) {
      case 1:
        return 10.00;
        break;
      case 4:
        return 30.00;
        break;
      case 5:
        return 20.00;
        break;
    }
  }
}
