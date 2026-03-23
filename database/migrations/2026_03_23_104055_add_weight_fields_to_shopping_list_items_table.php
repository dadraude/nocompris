<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->decimal('quantity', total: 8, places: 2)->unsigned()->default(1)->change();
            $table->string('quantity_unit')->default('u');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shopping_list_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->change();
            $table->dropColumn('quantity_unit');
        });
    }
};
