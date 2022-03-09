<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use ElliotGhorbani\LaravelSpreadsheet\Contracts\HasCustomExportAvailableColumns;
use ElliotGhorbani\LaravelSpreadsheet\Models\Spreadsheet;

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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('store Spreadsheet Error : ' . $e->getMessage());

            return [
                'error' => true,
                'message' => __(
                    'spreadsheet::exceptions.system_cant_action',
                    ['action' => __('spreadsheet::exceptions.create'), 'modelName' => __('spreadsheet::exceptions.spreadsheet')]
                ),
                'status' => Response::HTTP_BAD_REQUEST,
            ];
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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('update Spreadsheet Error : ' . $e->getMessage());

            return [
                'error' => true,
                'message' => __(
                    'spreadsheet::exceptions.system_cant_action',
                    ['action' => __('spreadsheet::exceptions.update'), 'modelName' => __('spreadsheet::exceptions.spreadsheet')]
                ),
                'status' => Response::HTTP_BAD_REQUEST,
            ];
        }

        DB::commit();

        return $spreadsheet;
    }

    /**
     * @param Model $modelObject Object.
     *
     * @return mixed
     */
    public function delete(Model $modelObject): mixed
    {
        $query = Spreadsheet::query();

        $model = $query->findOrFail($modelObject->{Spreadsheet::ID});

        return $model->delete();
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

        return $allTables;
    }

    /**
     * @param string $table
     *
     * @return array
     */
    public function getColumns(string $table): array
    {
        // doctrine does not support enum data type, we have to cast it as string
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

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
        // doctrine does not support enum data type, we have to cast it as string
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

        $tableModelMap = config('spreadsheet.table_model_map');

        if (
            isset($tableModelMap[$table])
            && is_subclass_of($tableModelMap[$table], HasCustomExportAvailableColumns::class)
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
}
