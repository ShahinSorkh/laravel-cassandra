<?php

namespace ShSo\Lacassa\Tests;

use DB;

use Cassandra\DefaultSession as CassandraSession;
use Cassandra\FutureRows;
use Cassandra\Rows;

use ShSo\Lacassa\Connection;
use ShSo\Lacassa\Query\Builder;
use ShSo\Lacassa\Schema\Builder as SchemaBuilder;
use ShSo\Lacassa\Schema\Grammar as SchemaGrammar;
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
        $this->assertInstanceOf(SchemaGrammar::class, $connection->getSchemaGrammar());
        $this->assertInstanceOf(SchemaBuilder::class, $connection->getSchemaBuilder());
    }

    public function testDisconnectAndReconnect()
    {
        $connection = DB::connection('cassandra');
        $this->assertNotNull($connection->getConnection());
        $connection->disconnect();
        $this->assertNull($connection->getConnection());
        $connection->statement('select * from users limit 1');
        $this->assertInstanceOf(CassandraSession::class, $connection->getConnection());
    }
}
