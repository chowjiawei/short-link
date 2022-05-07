<?php

namespace Chowjiawei\ShortLink\Providers;

use Illuminate\Support\ServiceProvider;

class ShortLinkServiceProvider extends ServiceProvider
{
    public function register()
    {
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../Config/short-link.php' => config_path('short-link.php'),
            __DIR__ . '/../Database/Migrations/2022_05_07_213807_create_redirects_table.php' => database_path('/migrations/2022_05_07_213807_create_redirects_table.php'),
        ]);
    }
}
