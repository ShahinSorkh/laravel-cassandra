<?php

namespace ShSo\Lacassa\Tests;

use DB;
use ShSo\Lacassa\Query\Builder as QueryBuilder;
use ShSo\Lacassa\TestCase;

class ConnectionTest extends TestCase
{

    private $session;
    private $conn;

    function setUp()
    {
        parent::setUp();

        $this->session = DB::getCassandraConnection();
        $this->session->execute('create table if not exists testconnection (id int primary key, name text, family text)');

        $this->conn = DB::connection('cassandra');
    }


    function testStatement()
    {
        $this->session->execute('insert into testconnection (id, name, family) values (?,?,?)', ['arguments' => [1, 'john', 'doe']]);
        $this->session->execute('insert into testconnection (id, name, family) values (?,?,?)', ['arguments' => [2, 'donald', 'trump']]);
        $this->session->execute('insert into testconnection (id, name, family) values (?,?,?)', ['arguments' => [3, 'jessica', 'alba']]);
        $this->assertEquals(3, $this->getRows()->count());

        // delete
        $this->conn->statement('delete from testconnection where id in (?,?)', [1, 3]);
        $this->assertEquals(1, $this->getRows()->count());

        // insert
        $this->conn->statement('insert into testconnection (id, name, family) values (?,?,?)', [4, 'Shahin', 'Sorkh']);
        $this->assertEquals(2, $this->getRows()->count());

        // update
        $this->assertEquals(
            ['id' => 2, 'name' => 'donald', 'family' => 'trump'],
            $this->getRows()->first()
        );
        $this->conn->statement('update testconnection set name=?, family=? where id=?', ['john', 'doe', 2]);
        $this->assertEquals(
            ['id' => 2, 'name' => 'john', 'family' => 'doe'],
            $this->getRows()->first()
        );

        // clean table
        $this->session->execute('delete from testconnection where id in (1,2,3,4)');
        $this->assertEquals(0, $this->getRows()->count());
    }

    function testTable()
    {
        $builder = $this->conn->table('testconnection');
        $this->assertTrue($builder instanceof QueryBuilder);
    }

    private function getRows()
    {
        return $this->conn->statement('select * from testconnection');
    }

}

