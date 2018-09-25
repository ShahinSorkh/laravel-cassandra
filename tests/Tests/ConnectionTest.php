<?php

namespace ShSo\Lacassa\Tests;

use Cassandra\DefaultSession as CassandraSession;
use Cassandra\FutureRows;
use Cassandra\Rows;
use DB;
use ShSo\Lacassa\Connection;
use ShSo\Lacassa\Query\Builder;
use ShSo\Lacassa\TestCase;

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
