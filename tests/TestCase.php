<?php

declare(strict_types=1);

namespace HotReloadStudios\Conductor\Tests;

use Closure;
use HotReloadStudios\Conductor\Conductor;
use HotReloadStudios\Conductor\ConductorServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use ReflectionProperty;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'HotReloadStudios\\Conductor\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    final public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function defineDatabaseMigrations(): void
    {
        foreach (glob(__DIR__.'/../database/migrations/*.php.stub') as $migration) {
            (include $migration)->up();
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ConductorServiceProvider::class,
        ];
    }

    protected function setConductorAuth(?Closure $callback = null): void
    {
        if ($callback instanceof Closure) {
            Conductor::auth($callback);

            return;
        }

        $property = new ReflectionProperty(Conductor::class, 'authUsing');
        $property->setValue(null, null);
    }
}
