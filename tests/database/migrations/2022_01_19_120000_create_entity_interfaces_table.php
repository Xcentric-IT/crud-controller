<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEntityInterfacesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('entity_interfaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('fqn');
            $table->timestamps();
        });

        Schema::create('entities_entity_interfaces', function (Blueprint $table) {
            $table->foreignUuid('entity_id')
                ->constrained('entities')
                ->cascadeOnDelete();

            $table->foreignUuid('entity_interface_id')
                ->constrained('entity_interfaces')
                ->cascadeOnDelete();

            $table->unique(['entity_id', 'entity_interface_id'], 'e_e_interfaces_e_id_e_interface_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('entities_entity_interfaces');
        Schema::dropIfExists('entity_interfaces');
    }
}
