<?php

use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

return [
    'spreadsheet_table' => Spreadsheet::TABLE,

    'middlewares' => ['api'],

    'prefix' => 'api',

    'csv_delimiter' => ',',

    'table_model_map' => [
        //example:
        //'users' => 'App\Models\User\User',
    ],

    'hidden_tables' => [],
];
