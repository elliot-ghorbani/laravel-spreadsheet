<?php

namespace ElliotGhorbani\LaravelSpreadsheet\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ElliotGhorbani\LaravelSpreadsheet\Services\Export;

class Spreadsheet extends Model
{
    use HasFactory;

    const TABLE = 'spreadsheets';

    const ID = 'id';
    const TABLE_NAME = 'table_name';
    const EXPORT_DATA = 'export_data';
    const IMPORT_DATA = 'import_data';

    const EXPORT_DATA_COLUMNS = 'columns';
    const EXPORT_DATA_FILTERS = 'filters';
    const EXPORT_DATA_FILTERS_COLUMN = Export::FILTERS_COLUMN;
    const EXPORT_DATA_FILTERS_OPERATOR = Export::FILTERS_OPERATOR;
    const EXPORT_DATA_FILTERS_VALUE = Export::FILTERS_VALUE;

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = config('spreadsheet.spreadsheet_table');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        self::TABLE_NAME,
        self::EXPORT_DATA,
        self::IMPORT_DATA,
    ];

    /**
     * @var array
     */
    protected $casts = [
        self::EXPORT_DATA => 'array',
        self::IMPORT_DATA => 'array',
    ];

    /**
     * Filter scope.
     *
     * @param Builder          $builder Builder.
     *
     * @return Builder
     */
    public function scopeFilter(Builder $builder)
    {
        if (request()->filled(self::TABLE_NAME)) {
            $builder->where(self::TABLE_NAME, request()->{self::TABLE_NAME});
        }

        // asc desc
        if (request()->filled('orderBy')) {
            $builder->orderBy(request()->orderBy);
        }

        return $builder;
    }
}
