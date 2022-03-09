<?php

use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

return [
    'spreadsheet_table' => Spreadsheet::TABLE,

    'middlewares' => ['api', 'auth:api'],

    'prefix' => 'api',

    'csv_delimiter' => ',',

    'table_model_map' => [
        //example:
        //'users' => 'App\Models\User\User',
    ],
];
