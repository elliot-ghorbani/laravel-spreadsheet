<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

class CreateSpreadsheetsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('spreadsheet.spreadsheet_table'), function (Blueprint $table) {
            $table->id();
            $table->string(Spreadsheet::TABLE_NAME);
            $table->json(Spreadsheet::EXPORT_DATA);
            $table->json(Spreadsheet::IMPORT_DATA);
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
        Schema::dropIfExists(config('spreadsheet.spreadsheet_table'));
    }
}
