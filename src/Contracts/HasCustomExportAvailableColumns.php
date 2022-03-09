<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Contracts;

interface HasCustomExportAvailableColumns
{
    /**
     * get spreadsheet export row
     *
     * @return array
     */
    public static function getSpreadsheetExportAvailableColumns(): array;
}
