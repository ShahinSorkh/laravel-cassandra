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
        // Create the connection
        $this->db = $config['keyspace'];
        $this->connection = $this->createConnection($config);
        $this->useDefaultPostProcessor();
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param string $collection
     *
     * @return \ShSo\Lacassa\Query\Builder
     */
    public function collection($collection)
    {
        $query = new Query\Builder($this);
        return $query->from($collection);
    }

    /**
     * Begin a fluent query against a database collection.
     *
     * @param string $table
     *
     * @return \ShSo\Lacassa\Query\Builder
     */
    public function table($table)
    {
        return $this->collection($table);
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
        return new Schema\Grammar;
    }

    /**
     * return Cassandra object.
     *
     * @return \Cassandra\DefaultSession
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
     * @return \Cassandra\DefaultSession
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
     * Execute an CQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        $statement = new Cassandra\SimpleStatement($query);
        return $this->getCassandraConnection()->execute($statement, ['arguments' => $bindings]);
    }

    /**
     * Execute an async CQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return bool
     */
    public function statementAsync($query, $bindings = [])
    {
        $statement = new Cassandra\SimpleStatement($query);
        return $this->getCassandraConnection()->executeAsync($statement, ['arguments' => $bindings])->get();
    }

    /**
     * Run an CQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        // For update or delete statements, we want to get the number of rows affected
        // by the statement and return that back to the developer. We'll first need
        // to execute the statement and then we'll use PDO to fetch the affected.
        foreach ($bindings as $binding) {
            $value = $value = 'string' == strtolower(gettype($binding)) ? "'" . $binding . "'" : $binding;
            $query = preg_replace('/\?/', $value, $query, 1);
        }
        $builder = new Query\Builder($this, $this->getPostProcessor());

        return $builder->execute($query);
    }

    /**
     * Execute an CQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     *
     * @return bool
     */
    public function raw($query)
    {
        $builder = new Query\Builder($this, $this->getPostProcessor());
        return $builder->execute($query);
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

