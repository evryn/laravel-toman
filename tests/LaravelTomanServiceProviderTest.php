<?php


namespace Evryn\LaravelToman\Tests;


final class LaravelTomanServiceProviderTest extends TestCase
{
    /** @test */
    public function publishes_config_correctly()
    {
        $this->artisan('vendor:publish', [
            '--provider' => 'Evryn\LaravelToman\LaravelTomanServiceProvider'
        ]);

        $this->assertFileExists(config_path('larapoke.php'));
        $this->assertFileIsReadable(config_path('larapoke.php'));
        $this->assertFileEquals(config_path('larapoke.php'), __DIR__ . '/../../config/larapoke.php');
        $this->assertTrue(unlink(config_path('larapoke.php')));
    }
}
