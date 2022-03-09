<?php

namespace ElliotGhorbani\LaravelSpreadsheet;

use Illuminate\Support\ServiceProvider;

class SpreadsheetServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/spreadsheet.php', 'spreadsheet'
        );

        $this->loadRoutesFrom(__DIR__ . '/routes/api.php');

        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        $this->publishes([
            __DIR__ . '/Config/spreadsheet.php' => config_path('spreadsheet.php'),
        ]);

        $this->loadTranslationsFrom(__DIR__.'/lang', 'spreadsheet');
    }
}
