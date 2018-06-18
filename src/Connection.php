<?php

namespace ShSo\Lacassa;

use Cassandra;
use Illuminate\Database\Connection as BaseConnection;

class Connection extends BaseConnection
{
    /**
     * The Cassandra connection handler.
     *
     * @var \Cassandra\DefaultSession
     */
    protected $connection;

    /**
     * Create a new database connection instance.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->db = $config['keyspace'];
        $this->connection = $this->createConnection($config);
        $this->useDefaultPostProcessor();
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     *
     * @return \ShSo\Lacassa\Query\Builder
     */
    public function table($table)
    {
        return $this->getDefaultQueryBuilder()->from($table);
    }

    /**
     * @return \ShSo\Lacassa\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return new Schema\Builder($this);
    }

    /**
     * Returns the connection grammer
     *
     * @return \ShSo\Lacassa\Schema\Grammar
     */
    public function getSchemaGrammar()
    {
        return new Schema\Grammar();
    }

    /**
     * return Cassandra object.
     *
     * @return \Cassandra\Session
     */
    public function getCassandraConnection()
    {
        return $this->connection;
    }

    /**
     * Create a new Cassandra connection.
     *
     * @param array $config
     *
     * @return \Cassandra\Session
     */
    protected function createConnection(array $config)
    {
        return Cassandra::cluster()
            ->withContactPoints($config['host'])
            ->withPort(intval($config['port']))
            ->build()->connect($config['keyspace']);
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        unset($this->connection);
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        return 'Cassandra';
    }

    /**
     * @return \ShSo\Lacassa\Query\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Query\Processor();
    }

    /**
     * @return \ShSo\Lacassa\Query\Builder
     */
    protected function getDefaultQueryBuilder()
    {
        return new Query\Builder($this, $this->getPostProcessor());
    }

    /**
     * @return \ShSo\Lacassa\Query\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new Query\Grammar();
    }

    /**
     * @return \ShSo\Lacassa\Schema\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new Schema\Grammar();
    }

    /**
     * Execute an CQL statement with bindings and return the result.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return Cassandra\Rows
     */
    public function statement($query, $bindings = [])
    {
        foreach ($bindings as $binding) {
            $value = 'string' == strtolower(gettype($binding)) ? "'" . $binding . "'" : $binding;
            $query = preg_replace('/\?/', $value, $query, 1);
        }
        return $this->getDefaultQueryBuilder()->executeCql($query);
    }

    /**
     * Execute an CQL statement and return the result.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return Cassandra\Rows
     */
    public function raw($query)
    {
        return $this->getDefaultQueryBuilder()->executeCql($query);
    }

    /**
     * Dynamically pass methods to the connection.
     *
     * @param string $method
     * @param array $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->connection, $method], $parameters);
    }
}

