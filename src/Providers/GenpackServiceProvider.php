<?php

namespace Nohara\Genpack\Providers;

use Illuminate\Support\ServiceProvider;

class GenpackServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind Core Builder ke container jika diperlukan
    }

    public function boot()
    {
        // Daftarkan views dari package agar bisa di-load oleh Laravel
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'genpack');

        // Opsional: publish asset/views agar bisa dicustom oleh user
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/genpack'),
        ], 'genpack-views');
    }
}
