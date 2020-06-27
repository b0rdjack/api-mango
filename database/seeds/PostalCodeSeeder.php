<?php

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PostalCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('postal_codes')->truncate();

        for ($i = 1; $i <= 20; $i++) {
            $code = '';
            ($i >= 10) ? $code = '750' . $i : $code = '7500' . $i;

            DB::table('postal_codes')->insert([
                'code' => $code,
                'city_id' => DB::table('cities')->where('label', 'Paris')->first()->id,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]);
        }
    }
}
