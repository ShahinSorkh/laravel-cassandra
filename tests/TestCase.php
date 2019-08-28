<?php

namespace ShSo\Lacassa;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp()
    {
        parent::setUp();
        if (
            ! file_exists(__DIR__.'/data/data.json') ||
            ! file_exists(__DIR__.'/data/users.json')
        ) {
            die('first run [php prepare_db.php]');
        }
    }

    protected function getPackageProviders($app)
    {
        return ['ShSo\\Lacassa\\CassandraServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'cassandra');
        $app['config']->set('database.connections.cassandra', [
            'driver' => 'cassandra',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'keyspace' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'page_size' => '5000', // defaults to 5000
            'consistency' => 'local_one',
            'timeout' => null,
            'connect_timeout' => 5.0,
            'request_timeout' => 12.0,
        ]);
    }
}
