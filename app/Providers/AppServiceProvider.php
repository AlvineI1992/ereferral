<?php

namespace App\Providers;

use App\Database\Schema\Grammars\LegacyMySqlGrammar;
use App\OpenApi\ApiDocumentTransformer;
use App\OpenApi\ApiOperationTransformer;
use Dedoc\Scramble\Scramble;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use ParagonIE\CipherSweet\Backend\FIPSCrypto;
use ParagonIE\CipherSweet\CipherSweet;
use ParagonIE\CipherSweet\KeyProvider\StringProvider;

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
        if (! env('CIPHERSWEET_ENABLED', false)) {
            return;
        }

        $this->app->singleton(\ParagonIE\CipherSweet\CipherSweet::class, function ($app) {

            $rawKey = env('CIPHERSWEET_KEY');

            if (! $rawKey) {
                throw new \RuntimeException('CIPHERSWEET_KEY not set.');
            }

            // Detect format
            if (str_starts_with($rawKey, 'base64:')) {
                $decodedKey = base64_decode(substr($rawKey, 7), true);
            } else {
                $decodedKey = hex2bin($rawKey);
            }

            if (! $decodedKey || strlen($decodedKey) !== 32) {
                throw new \RuntimeException('Invalid CipherSweet key size. Must be 32 bytes.');
            }

            $provider = new \ParagonIE\CipherSweet\KeyProvider\StringProvider($decodedKey);
            $backend = new \ParagonIE\CipherSweet\Backend\FIPSCrypto;

            return new \ParagonIE\CipherSweet\CipherSweet($provider, $backend);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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
