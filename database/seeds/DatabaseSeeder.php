<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleSeeder::class,
            StateSeeder::class,
            CategorySubCategorySeeder::class,
            QuantitySeeder::class,
            CitySeeder::class,
            PostalCodeSeeder::class,
            TagSeeder::class,
            TransportSeeder::class
        ]);
    }
}
