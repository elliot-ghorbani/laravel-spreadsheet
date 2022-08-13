<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Repositories;

use ElliotGhorbani\LaravelSpreadsheet\Contracts\HasCustomExportAvailableColumns;
use ElliotGhorbani\LaravelSpreadsheet\Contracts\HasCustomExportAvailableFilterColumns;
use ElliotGhorbani\LaravelSpreadsheet\Exceptions\DestroySpreadsheetException;
use ElliotGhorbani\LaravelSpreadsheet\Exceptions\StoreSpreadsheetException;
use ElliotGhorbani\LaravelSpreadsheet\Exceptions\UpdateSpreadsheetException;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SpreadsheetRepository
{
    /**
     * @param array $data Data.
     *
     * @return array|Spreadsheet
     */
    public function store(array $data): array|Spreadsheet
    {
        DB::beginTransaction();

        try {
            $this->prepareExportData($data);

            $item = Spreadsheet::create($data);
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('LaravelSpreadsheet: ' . $exception->getMessage());

            throw new StoreSpreadsheetException();
        }

        DB::commit();

        return $item;
    }

    /**
     * @param Spreadsheet $spreadsheet Spreadsheet.
     * @param array    $data     Data.
     *
     * @return array|Spreadsheet
     */
    public function update(Spreadsheet $spreadsheet, array $data): array|Spreadsheet
    {
        DB::beginTransaction();

        try {
            $this->prepareExportData($data);

            $spreadsheet->{Spreadsheet::TABLE_NAME} = $data[Spreadsheet::TABLE_NAME];
            $spreadsheet->{Spreadsheet::EXPORT_DATA} = $data[Spreadsheet::EXPORT_DATA];
            $spreadsheet->{Spreadsheet::IMPORT_DATA} = $data[Spreadsheet::IMPORT_DATA];
            $spreadsheet->save();

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error('LaravelSpreadsheet: ' . $exception->getMessage());

            throw new UpdateSpreadsheetException();
        }

        return $spreadsheet;
    }

    /**
     * @param Spreadsheet $modelObject Object.
     *
     * @return void
     */
    public function delete(Spreadsheet $modelObject): void
    {
        try {
            $modelObject->delete();
        } catch (\Exception $exception) {
            Log::error('LaravelSpreadsheet: ' . $exception->getMessage());

            throw new DestroySpreadsheetException();
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareExportData(array &$data): void
    {
        $data[Spreadsheet::EXPORT_DATA][Spreadsheet::EXPORT_DATA_FILTERS] = $this->getFilters($data);

        $columns = $data[Spreadsheet::EXPORT_DATA][Spreadsheet::EXPORT_DATA_COLUMNS];
        ksort($columns);
        $data[Spreadsheet::EXPORT_DATA][Spreadsheet::EXPORT_DATA_COLUMNS] = array_values($columns);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getFilters(array $data): array
    {
        $filters = [];
        foreach ($data[Spreadsheet::EXPORT_DATA][Spreadsheet::EXPORT_DATA_FILTERS] as $key => $filter) {
            if (is_array($filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE])) {
                sort($filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE]);

                if (
                    in_array(
                        Schema::getColumnType($data[Spreadsheet::TABLE_NAME], $filter[Spreadsheet::EXPORT_DATA_FILTERS_COLUMN]),
                        ['datetime', 'date']
                    )
                ) {
                    $filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE][0] = Carbon::create($filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE][0]);
                    $filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE][1] = Carbon::create($filter[Spreadsheet::EXPORT_DATA_FILTERS_VALUE][1])->endOfDay();
                }
            }

            $filters[$key] = $filter;
        }
        return $filters;
    }

    /**
     * @return array|false[]
     */
    public function getAllTables(): array
    {
        $allTables = Schema::getAllTables();

        $allTables = array_map(
            function ($value) {
                return reset($value);
            },
            $allTables
        );

        $hiddenTables = (array)config('spreadsheet.hidden_tables');
        if (count($hiddenTables)) {
            $allTables = array_filter(
                $allTables,
                function ($value) use ($hiddenTables) {
                    if (in_array($value, $hiddenTables)) {
                        return false;
                    }

                    return true;
                },
            );
        }

        return $allTables;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    public function getColumns(string $table): array
    {
        $this->castEnumToString();

        $tableModelMap = config('spreadsheet.table_model_map');

        if (
            isset($tableModelMap[$table])
            && is_subclass_of($tableModelMap[$table], HasCustomExportAvailableColumns::class)
        ) {
            $columns = $tableModelMap[$table]::getSpreadsheetExportAvailableColumns();
        } else {
            $columns = Schema::getColumnListing($table);
        }

        return $columns;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    public function getFilterColumns(string $table): array
    {
        $this->castEnumToString();

        $tableModelMap = config('spreadsheet.table_model_map');

        if (
            isset($tableModelMap[$table])
            && is_subclass_of($tableModelMap[$table], HasCustomExportAvailableFilterColumns::class)
        ) {
            $columns = $tableModelMap[$table]::getSpreadsheetExportAvailableFilterColumns();
        } else {
            $columns = DB::getSchemaBuilder()->getColumnListing($table);
        }

        $columnTypes = [];
        foreach ($columns as $column) {
            $columnTypes[$column] = DB::getSchemaBuilder()->getColumnType($table, $column);
        }

        return $columnTypes;
    }

    /**
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    protected function castEnumToString(): void
    {
        // doctrine does not support enum data type, we have to cast it as string
        DB::getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
}
