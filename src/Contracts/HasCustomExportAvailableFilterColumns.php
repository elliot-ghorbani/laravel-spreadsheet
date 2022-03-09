<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Contracts;

interface HasCustomExportAvailableFilterColumns
{
    /**
     * get spreadsheet export row
     *
     * @return array
     */
    public static function getSpreadsheetExportAvailableFilterColumns(): array;
}
