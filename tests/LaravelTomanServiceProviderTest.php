<?php

namespace Evryn\LaravelToman\Tests;

use Illuminate\Support\Facades\File;

final class LaravelTomanServiceProviderTest extends TestCase
{
    /** @test */
    public function publishes_config_correctly()
    {
        // We need to ensure that artisan can publish `toman.php` config file properly

        $source = __DIR__.'/../config/toman.php';
        $dest = config_path('toman.php');

        File::delete($dest);

        $this->artisan('vendor:publish', [
            '--provider' => 'Evryn\LaravelToman\LaravelTomanServiceProvider',
            '--tag' => 'config',
        ]);

        $this->assertFileExists($dest);
        $this->assertFileIsReadable($dest);
        $this->assertFileEquals($dest, $source);
    }

    /** @test */
    public function publishes_translations_correctly()
    {
        // We need to ensure that artisan can publish default translations files properly

        $map = [
            __DIR__.'/../resources/lang/en/zarinpal.php' => resource_path('lang/vendor/toman/en/zarinpal.php'),
            __DIR__.'/../resources/lang/fa/zarinpal.php' => resource_path('lang/vendor/toman/fa/zarinpal.php'),
        ];

        foreach (array_values($map) as $dest) {
            File::delete($dest);
        }

        $this->artisan('vendor:publish', [
            '--provider' => 'Evryn\LaravelToman\LaravelTomanServiceProvider',
            '--tag' => 'lang',
        ]);

        foreach ($map as $source => $dest) {
            $this->assertFileExists($dest);
            $this->assertFileIsReadable($dest);
            $this->assertFileEquals($dest, $source);
        }
    }
}
