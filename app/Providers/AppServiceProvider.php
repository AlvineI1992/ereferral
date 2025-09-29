<?php

namespace App\Providers;

/* use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\KeyProvider\StringProvider; */

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Dedoc\Scramble\Scramble;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
       /*  $this->app->singleton(CipherSweet::class, function ($app) {
            $key = base64_decode(str_replace('base64:', '', env('CIPHERSWEET_KEY')));
            $provider = new StringProvider($key);
            $backend = new FIPSCrypto();
    
            return new CipherSweet($provider, $backend);
        }); */
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
		if (config('app.env') === 'production') {
			URL::forceScheme('https');
		}
        

        
    }
}
