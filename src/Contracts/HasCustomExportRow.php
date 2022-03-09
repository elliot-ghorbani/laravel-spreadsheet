<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Contracts;

interface HasCustomExportRow
{
    /**
     * get spreadsheet export row
     *
     * @param array $columns
     *
     * @return array
     */
    public function getSpreadsheetExportRow(array $columns): array;
}
