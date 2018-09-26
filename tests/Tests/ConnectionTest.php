<?php

namespace ShSo\Lacassa\Tests;

use DB;
use Cassandra\Rows;
use Cassandra\FutureRows;
use ShSo\Lacassa\TestCase;
use ShSo\Lacassa\Connection;
use ShSo\Lacassa\Query\Builder;
use Cassandra\DefaultSession as CassandraSession;

class ConnectionTest extends TestCase
{
    public function testNewConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(CassandraSession::class, $connection->getConnection());
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('cassandra', $connection->getDriverName());
    }

    public function testDynamicMethods()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Rows::class, $connection->execute('select * from users'));
        $this->assertInstanceOf(FutureRows::class, $connection->executeAsync('select * from users'));
        $this->assertInternalType('array', $connection->metrics());
    }

    public function testInstanciatingFromConnection()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(Builder::class, $connection->table('foo'));
    }

    public function testDisconnect()
    {
        $connection = DB::connection('cassandra');
        $this->assertNotNull($connection->getConnection());
        $connection->disconnect();
        $this->assertNull($connection->getConnection());
    }
}
