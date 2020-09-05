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
      'Cinéma', //1
      'Musée', //2
      'Café', //3
      'Salon de thé', //4
      'Bar', //5
      'Restaurant', //6
      'Fast food', //7
      'Jardin', //8
    ];

    for ($i = 0; $i < count($subcategory_labels); $i++) {
      $subcategories = $this->createSubcategory($i + 1, $subcategory_labels[$i], $subcategories);
    }

    // Seed relations
    $categories[1]->subcategories()->sync(1, 2);
    $categories[2]->subcategories()->sync(1);
    $categories[3]->subcategories()->sync(3, 4, 5, 6, 7);
    $categories[4]->subcategories()->sync(8);
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
