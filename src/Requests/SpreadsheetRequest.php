<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use ElliotGhorbani\LaravelSpreadsheet\Repositories\SpreadsheetRepository;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;
use ElliotGhorbani\LaravelSpreadsheet\Services\Export;

class SpreadsheetRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(SpreadsheetRepository $repository): array
    {
        $rules = [
            Spreadsheet::TABLE_NAME => [
                'required',
                'string',
                Rule::in($repository->getAllTables())
            ],
            Spreadsheet::EXPORT_DATA => 'required|array',
            Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_COLUMNS => 'required|array',
            Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_COLUMNS . '.*'
                => Rule::in($repository->getColumns($this->{Spreadsheet::TABLE_NAME})),
            Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_FILTERS => 'array',
            Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_FILTERS . '.*.' . Spreadsheet::EXPORT_DATA_FILTERS_COLUMN
                => Rule::in(array_keys($repository->getFilterColumns($this->{Spreadsheet::TABLE_NAME}))),

            //TODO: make IMPORT_DATA required after csv import implementation
            Spreadsheet::IMPORT_DATA => 'array',
        ];

        foreach ($this->{Spreadsheet::EXPORT_DATA}[Spreadsheet::EXPORT_DATA_FILTERS] as $key => $filter) {
            $rules[Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_FILTERS . '.' . $key . '.' . Spreadsheet::EXPORT_DATA_FILTERS_OPERATOR]
                = Rule::in(Export::OPERATORS);

            $valueRuleKey = Spreadsheet::EXPORT_DATA . '.' . Spreadsheet::EXPORT_DATA_FILTERS . '.' . $key . '.' . Spreadsheet::EXPORT_DATA_FILTERS_VALUE;
            if ($filter[Spreadsheet::EXPORT_DATA_FILTERS_OPERATOR] == Export::BETWEEN) {
                $rules[$valueRuleKey] = 'required|array';
            } else {
                $rules[$valueRuleKey] = 'required';
            }
        }

        return $rules;
    }
}
