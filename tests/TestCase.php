<?php

namespace ShSo\Lacassa;

use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{

    protected function getPackageProviders($app)
    {
        return ['ShSo\\Lacassa\\CassandraServiceProvider'];
    }

    protected function setUp()
    {
        parent::setUp();

        // set up a fake database
    }

    protected function tearDown()
    {
        parent::tearDown();

        // drop the fake database
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'cassandra');
        $app['config']->set('database.connections.cassandra', [
            'driver' => 'cassandra',
        ]);
    }

}

