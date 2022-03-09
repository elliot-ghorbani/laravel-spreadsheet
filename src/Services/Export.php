<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use ElliotGhorbani\LaravelSpreadsheet\Repositories\SpreadsheetRepository;
use ElliotGhorbani\LaravelSpreadsheet\Contracts\HasCustomExportRow;

class Export
{
    const FILTERS_COLUMN = 'column';
    const FILTERS_OPERATOR = 'operator';
    const FILTERS_VALUE = 'value';

    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const LESS_THAN = '<';
    const GREATER_THAN = '>';
    const BETWEEN = '<>';

    const OPERATORS = [
        self::EQUAL,
        self::NOT_EQUAL,
        self::LESS_THAN,
        self::GREATER_THAN,
        self::BETWEEN,
    ];

    private string $table;
    private array $desiredColumns;
    private array $filters;
    private array $columnTypes;
    private string $delimiter;
    private ?string $model = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(string $tableName, array $columns, array $filters)
    {
        $this->table = $tableName;
        $this->desiredColumns = $columns;
        $this->filters = $filters;
        $this->initModel();

        $this->columnTypes = (new SpreadsheetRepository)->getFilterColumns($this->table);

        $this->delimiter = config('spreadsheet.csv_delimiter');
    }

    /**
     * @return void
     *
     * @throws \Exception
     */
    protected function initModel(): void
    {
        $tableModelMap = config('spreadsheet.table_model_map');

        if (!isset($tableModelMap[$this->table])) {
            $this->desiredColumns = array_intersect(
                $this->desiredColumns,
                DB::getSchemaBuilder()->getColumnListing($this->table)
            );

            return;
        }

        if (!is_subclass_of($tableModelMap[$this->table], Model::class)) {
            throw new \Exception('Specified Model should be a subclass of '. Model::class);
        }

        $this->model = $tableModelMap[$this->table];
    }

    /**
     * @param int $limit
     * @param int|null $offset
     *
     * @return void
     */
    public function setLimitOffset(int $limit, ?int $offset = null): void
    {
        $this->limit = $limit;
        $this->offset = $offset;
    }

    /**
     * override delimiter that is set in config
     *
     * @param string $delimiter
     */
    public function setDelimiter(string $delimiter): void
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return Collection
     */
    protected function getCollection(): Collection
    {
        $query = $this->getQuery();

        $this->applyFilter($query);

        return $query->get();
    }

    /**
     * @param  \stdClass|Model  $row
     * @return array
     */
    protected function map(\stdClass|Model $row): array
    {
        if (is_subclass_of($this->model, HasCustomExportRow::class)) {
            $rawResult = $row->getSpreadsheetExportRow($this->desiredColumns);

            //Check to make sure columns that are returned from function are correct
            $result = array_intersect_key($rawResult, array_flip($this->desiredColumns));
        } else {
            foreach ($this->desiredColumns as $column) {
                $result[$column] = $row->{$column};
            }
        }

        return $result;
    }

    /**
     * apply filter
     *
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     *
     * @return void
     */
    protected function applyFilter(\Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder &$query): void
    {
        foreach ($this->filters as $item) {
            if ($item[self::FILTERS_OPERATOR] == self::BETWEEN) {
                $query->whereBetween($item[self::FILTERS_COLUMN], $item[self::FILTERS_VALUE]);

                continue;
            }

            if (in_array($this->columnTypes[$item[self::FILTERS_COLUMN]], ['datetime', 'date'])) {
                $query->whereDate($item[self::FILTERS_COLUMN], $item[self::FILTERS_OPERATOR], $item[self::FILTERS_VALUE]);

                continue;
            }

            $query->where($item[self::FILTERS_COLUMN], $item[self::FILTERS_OPERATOR], $item[self::FILTERS_VALUE]);
        }

        if ($this->limit) {
            $query->take($this->limit);
        }

        if ($this->offset) {
            $query->skip($this->offset);
        }
    }

    /**
     * get query
     *
     * @return \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
     */
    protected function getQuery(): \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder
    {
        if ($this->model) {
            $query = $this->model::query();
        } else {
            $query = DB::table($this->table);
        }

        return $query;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download()
    {
        $collection = $this->getCollection()
            ->map(
                function ($row) {
                    return $this->map($row);
                }
            )->toArray();

        return Response::stream(
            function() use($collection) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $this->desiredColumns, $this->delimiter);

                foreach ($collection as $row) {
                    fputcsv($file, (array)$row, $this->delimiter);
                }

                fclose($file);
            },
            200,
            [
                'Content-type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename=' . $this->table . '.csv',
                'Content-Transfer-Encoding' => 'binary',
            ]
        );
    }
}
