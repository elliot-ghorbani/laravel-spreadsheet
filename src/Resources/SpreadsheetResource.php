<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

class SpreadsheetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request Request.
     *
     * @return  array
     */
    public function toArray($request): array
    {
        return [
            Spreadsheet::ID => $this->{Spreadsheet::ID},
            Spreadsheet::TABLE_NAME => $this->{Spreadsheet::TABLE_NAME},
            Spreadsheet::EXPORT_DATA => $this->{Spreadsheet::EXPORT_DATA},
            Spreadsheet::IMPORT_DATA => $this->{Spreadsheet::IMPORT_DATA},
        ];
    }
}
