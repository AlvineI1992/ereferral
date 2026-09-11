<?php

namespace App\Providers;

use App\Database\Schema\Grammars\LegacyMySqlGrammar;
use App\OpenApi\ApiDocumentTransformer;
use App\OpenApi\ApiOperationTransformer;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use App\Auth\CipherSweetUserProvider;
use Illuminate\Support\Facades\Auth;
use App\Services\DataEncryptionManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    /*  public function register(): void
     {
         $this->app->singleton(CipherSweet::class, function ($app) {
             $key = base64_decode(str_replace('base64:', '', env('CIPHERSWEET_KEY')));
             $provider = new StringProvider($key);
             $backend = new FIPSCrypto();

             return new CipherSweet($provider, $backend);
         });
     } */

    public function register(): void
    {
        // CipherSweet is registered by the package service provider.
        $this->app->scoped(DataEncryptionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('ciphersweet-eloquent', function ($app, array $config) {
            return new CipherSweetUserProvider($app['hash'], $config['model']);
        });
        $connection = DB::connection();

        if ($connection->getDriverName() === 'mysql') {
            $connection->setSchemaGrammar(new LegacyMySqlGrammar($connection));
        }

        Scramble::configure()
            ->withDocumentTransformers(ApiDocumentTransformer::class)
            ->withOperationTransformers(ApiOperationTransformer::class);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
