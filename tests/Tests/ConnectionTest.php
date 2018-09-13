<?php

namespace ShSo\Lacassa\Tests;

use DB;
use Cassandra\DefaultSession as CassandraSession;
use ShSo\Lacassa\Connection;
use ShSo\Lacassa\TestCase;

class ConnectionTest extends TestCase
{
    function testNewConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertTrue($connection->getCassandraConnection() instanceof CassandraSession);
        $this->assertTrue($connection instanceof Connection);
    }
}

