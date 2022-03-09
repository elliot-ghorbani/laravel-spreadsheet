<?php

use ElliotGhorbani\LaravelSpreadsheet\Controllers\SpreadsheetController;
use Illuminate\Support\Facades\Route;

Route::group(
    [
        'prefix' => config('spreadsheet.prefix'),
        'middleware' => config('spreadsheet.middlewares'),
    ],
    function () {
        Route::get('spreadsheet/export/{spreadsheet}',  [SpreadsheetController::class, 'export']);
        Route::get('spreadsheet/import/{spreadsheet}',  [SpreadsheetController::class, 'import']);
        Route::get('spreadsheet/tables',  [SpreadsheetController::class, 'getTables']);
        Route::post('spreadsheet/columns',  [SpreadsheetController::class, 'getColumns']);
        Route::post('spreadsheet/filter-columns',  [SpreadsheetController::class, 'getFilterColumns']);
        Route::apiResource('spreadsheet', SpreadsheetController::class);
    }
);
