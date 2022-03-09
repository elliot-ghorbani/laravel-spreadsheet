<h1 align="center">Laravel Spreadsheet</h1>

<p align="center">
<a href="https://packagist.org/packages/elliotghorbani/laravel-spreadsheet"><img src="https://img.shields.io/packagist/dt/elliotghorbani/laravel-spreadsheet" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/elliotghorbani/laravel-spreadsheet"><img src="https://img.shields.io/packagist/v/elliotghorbani/laravel-spreadsheet" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/elliotghorbani/laravel-spreadsheet"><img src="https://img.shields.io/packagist/l/elliotghorbani/laravel-spreadsheet" alt="License"></a>
</p>

## About this package

This package allows you to export from your database as csv dynamically.

NOTE: Import feature is not added yet.

## Installation

composer require elliotghorbani/laravel-spreadsheet

php artisan vendor:publish --provider="ElliotGhorbani\LaravelSpreadsheet\SpreadsheetServiceProvider"

In the generated config file you can override the table name under "spreadsheet_table" key.

php artisan migrate

## Usage

1. Make a GET request to "/spreadsheet/tables"
It returns all tables of your database.

2. Make a POST request to "/spreadsheet/columns" with a parameter name "table_name".
<pre>
{
    "table_name": "users"
}
</pre>
It returns all columns of the specified table. So than you can select desired columns and their position to be included in the spreadsheet. Please read NOTE 6.

3. Make a POST request to "/spreadsheet/filter-columns" with a parameter name "table_name".
<pre>
{
    "table_name": "users"
}
</pre>
It returns all columns of the specified table that can be filtered with their datatype so that you can generate a form. Please read NOTE 7.

4. Make a POST request to "/spreadsheet" with below parameters.
<pre>
{
    "table_name": "users",
    "export_data":
        {
            "columns": {"2": "id", "1": "email", "3": "username"},
            "filters": [
                {"column": "id", "operator": "<", "value": "60"}
            ]
        },
    "import_data": []
}
</pre>
Your desired criteria is now created on database. So that you can use it again later.

5. Make a GET request to "/spreadsheet/export/{export}"

A csv file containing the desired column and their positions with the filter applied is returned.

NOTE 1: "/spreadsheet" is a restfull route.

NOTE 2: Supported operators are "=", "!=", "<", ">", "<>". The last one ("<>") act as between.
<pre>
{
    "table_name": "users",
    "export_data":
        {
            "columns": {"2": "id", "1": "email", "3": "username"},
            "filters": [
                {"column": "id", "operator": "<>", "value": ["60", "100"]}
            ]
        },
    "import_data": []
}
</pre>

NOTE 3: You can add middlewares and a prefix to routes of this package in config.

NOTE 4: You change csv delimiter in config.

NOTE 5: If you want to modify returned rows (unset or add a column for example), you can implement HasCustomExportRow interface in you eloquent modal and add it in config file under "table_model_map" key*.
<pre>
class User extends Authenticatable implements HasCustomExportRow
{
    public function getSpreadsheetExportRow(array $columns): array
    {
        $attributes = $this->attributes;
        unset($attributes['password']);

        if (array_search('full_name', $columns)) {
            $attributes['full_name'] = $this->people->first_name . ' ' . $this->people->last_name;
        }

        $result = [];
        foreach ($columns as $column) {
            $result[$column] = $attributes[$column];
        }

        return $result;
    }
}
</pre>

NOTE 6: If you want to unset a column so that user is not able to request it, you can implement HasCustomExportAvailableColumns interface.
<pre>
class User extends Authenticatable implements HasCustomExportAvailableColumns
{
    public static function getSpreadsheetExportAvailableColumns(): array
    {
        $columns = Schema::getColumnListing('users');

        $passwordKey = array_search('password', $columns);
        unset($columns[$passwordKey]);

        $columns[] = 'full_name';

        return $columns;
    }
}
</pre>

NOTE 7: If you want to unset a column so that user is not able to filter it, you can implement HasCustomExportAvailableFilterColumns interface.
<pre>
class User extends Authenticatable implements HasCustomExportAvailableFilterColumns
{
    public static function getSpreadSheetExportAvailableFilterColumns(): array
    {
        $columns = Schema::getColumnListing('users');

        $passwordKey = array_search('password, $columns);
        unset($columns[$passwordKey]);

        return $columns;
    }
}
</pre>

*
<pre>
    'table_model_map' => [
        //'Table Name' => 'Eloquent Model Class'
        'users' => 'App\Models\User\User',
    ],
</pre>


## Contributing

Thank you for considering contributing to the Laravel Spreadsheet!

## License

The Laravel Spreadsheet is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
