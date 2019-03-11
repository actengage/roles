<?php

namespace Tests;

use Illuminate\Support\Facades\Storage;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function user()
    {
        $model = app(\Illuminate\Foundation\Auth\User::class);
        $model->name = 'test';
        $model->email = 'test@test.com';
        $model->password = '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm'; // secret
        $model->remember_token = str_random(10);
        $model->save();

        return $model;
    }

    /**
    * Setup the test environment.
    */
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations();

        $this->artisan('migrate', [
            '--database' => 'testbench'
        ]);

        Storage::fake('local');
        Storage::fake('public');
        Storage::fake('s3');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [
            \Actengage\Roles\RolesServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            //
        ];
    }

}
