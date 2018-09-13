<?php

namespace ShSo\Lacassa;

use Cassandra;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{

    protected function getPackageProviders($app)
    {
        return ['ShSo\\Lacassa\\CassandraServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'cassandra');
        $app['config']->set('database.connections.cassandra', [
            'driver' => 'Cassandra',
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'keyspace' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME', ''),
            'password' => env('DB_PASSWORD', ''),
            'page_size' => '20000', # defaults to 5000
            'consistency' => 'two',
            'timeout' => 10.0,
            'connect_timeout' => 3.0,
            'request_timeout' => 3.0,
        ]);
    }

}

