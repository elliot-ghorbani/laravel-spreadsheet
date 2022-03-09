<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ElliotGhorbani\LaravelSpreadsheet\Repositories\SpreadsheetRepository;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

class TableRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(SpreadsheetRepository $spreadsheetRepository): array
    {
        return [
            Spreadsheet::TABLE_NAME => ['required', 'string', Rule::in($spreadsheetRepository->getAllTables())],
        ];
    }
}
