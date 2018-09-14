<?php

namespace ShSo\Lacassa\Tests;

use DB;
use Cassandra\DefaultSession as CassandraSession;
use Cassandra\{FutureRows, Rows};
use ShSo\Lacassa\Connection;
use ShSo\Lacassa\Query\{Builder, Grammar, Processor};
use ShSo\Lacassa\TestCase;

class ConnectionTest extends TestCase
{
    function testNewConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(CassandraSession::class, $connection->getConnection());
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('cassandra', $connection->getDriverName());
    }

    function testDynamicMethods()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Rows::class, $connection->execute('select * from users'));
        $this->assertInstanceOf(FutureRows::class, $connection->executeAsync('select * from users'));
        $this->assertInternalType('array', $connection->metrics());
    }

    function testInstanciatingFromConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Builder::class, $connection->table('foo'));
    }

    function testDisconnect()
    {
        $connection = DB::connection('cassandra');
        $this->assertNotNull($connection->getConnection());
        $connection->disconnect();
        $this->assertNull($connection->getConnection());
    }
}

