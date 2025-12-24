<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Translation Loader
    |--------------------------------------------------------------------------
    |
    | This option controls the default translation "loader" that will be used
    | to load translation strings. The "file" loader will load translations
    | from the filesystem, while the "database" loader will load them from
    | the database.
    |
    */

    'loader' => env('TRANSLATION_LOADER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Translation File Path
    |--------------------------------------------------------------------------
    |
    | This option determines the path where translation files are stored.
    | By default, Laravel looks for translation files in the lang directory
    | at the root of your application.
    |
    */

    'path' => base_path('lang'),

    /*
    |--------------------------------------------------------------------------
    | Translation Cache
    |--------------------------------------------------------------------------
    |
    | This option determines whether translation strings should be cached
    | for better performance. When enabled, translations will be cached
    | and only reloaded when the cache is cleared.
    |
    */

    'cache' => env('TRANSLATION_CACHE', false),

];