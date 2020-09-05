<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('address');
            $table->char('siren',9);
            $table->char('phone_number',10);
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->integer('opening_hours');
            $table->integer('closing_hours');
            $table->integer('average_time_spent');
            $table->tinyInteger('disabled_access');
            $table->foreignId('postal_code_id')->constrained();
            $table->foreignId('professional_id')->nullable()->constrained();
            $table->foreignId('subcategory_id')->constrained();
            $table->foreignId('state_id')->constrained();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activities');
    }
}
