<?php

use App\Category;
use App\Subcategory;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySubCategorySeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    DB::table('categories')->truncate();
    DB::table('subcategories')->truncate();
    DB::table('category_subcategory')->truncate();

    // Seed categories
    $categories = [];
    $category_labels = [
      'Culturel',
      'Divertissement',
      'Restauration',
      'Publique'
    ];

    for ($i = 0; $i < count($category_labels); $i++) {
      $categories = $this->createCategory($i + 1, $category_labels[$i], $categories);
    }

    // Seed subcategories
    $subcategories = [];
    $subcategory_labels = [
      'Théâtre', //1
      'Concert', //2
      'Cinéma', //3
      'Musée', //4
      'Spectacle', //5
      'Monument', //6
      'Atelier', //7
      'Boîte', //8
      'Escape game', //9
      'Bowling', //10
      'Piscine', //11
      'Centre sportif', //12
      'Centre commercial', //13
      'Lieu de tournage', //14
      'Café', //15
      'Salon de thé', //16
      'Bar', //17
      'Restaurant', //18
      'Street food', //19
      'Fast food', //20
      'Glacier', //21
      'Parc', //22
      'Jardin', //23
      'Berge', //24
      'Pont', //25
      'Quartier', //26
    ];

    for ($i = 0; $i < count($subcategory_labels); $i++) {
      $subcategories = $this->createSubcategory($i + 1, $subcategory_labels[$i], $subcategories);
    }

    // Seed relations
    $categories[1]->subcategories()->sync(1, 2, 3, 4, 5, 6);
    $categories[2]->subcategories()->sync(2, 3, 7, 8, 9, 10, 11, 12, 13, 14);
    $categories[3]->subcategories()->sync(15, 16, 17, 18, 19, 20, 21);
    $categories[4]->subcategories()->sync(22, 23, 24, 25, 26);
  }

  /**
   * Create a category and added it to an array
   */
  private function createCategory($id, $label, $categories)
  {
    $category = new Category([
      'id' => $id,
      'label' => $label,
      'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
      'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
    ]);
    $category->save();
    $categories[$category->id] = $category;
    return $categories;
  }

  /**
   * Create a subcategory and added it to an array
   */
  private function createSubcategory($id, $label, $subcategories)
  {
    $subcategory = new Subcategory([
      'id' => $id,
      'label' => $label,
      'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
      'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
    ]);
    $subcategory->save();
    $subcategories[$subcategory->id] = $subcategory;
    return $subcategories;
  }
}
