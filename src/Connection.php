<?php

namespace ShSo\Lacassa;

use Cassandra;
use ShSo\Lacassa\Query\Builder as QueryBuilder;
use ShSo\Lacassa\Query\Grammar as QueryGrammar;
use ShSo\Lacassa\Schema\Builder as SchemaBuilder;
use ShSo\Lacassa\Schema\Grammar as SchemaGrammar;
use ShSo\Lacassa\Query\Processor as QueryProcessor;
use Illuminate\Database\Connection as BaseConnection;
use Cassandra\Exception\RuntimeException as CassandraRuntimeException;

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
        $this->useDefaultSchemaGrammar();
        $this->useDefaultQueryGrammar();
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     * @param null $as
     * @return \ShSo\Lacassa\Query\Builder
     */
    public function table($table, $as = null)
    {
        return $this->getDefaultQueryBuilder()->from($table);
    }

    /**
     * @return \ShSo\Lacassa\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        return new SchemaBuilder($this);
    }

    /**
     * Returns the connection grammer.
     *
     * @return \ShSo\Lacassa\Schema\Grammar
     */
    public function getSchemaGrammar()
    {
        return new SchemaGrammar();
    }

    /**
     * return Cassandra object.
     *
     * @return \Cassandra\Session
     */
    public function getConnection()
    {
        return $this->connection ?? null;
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
        $builder = Cassandra::cluster()
            ->withContactPoints($config['host'] ?? '127.0.0.1')
            ->withPort(intval($config['port'] ?? '7000'));
        if (array_key_exists('page_size', $config) && ! empty($config['page_size'])) {
            $builder->withDefaultPageSize(intval($config['page_size'] ?? '5000'));
        }
        if (array_key_exists('consistency', $config) && in_array(strtoupper($config['consistency']), [
                'ANY', 'ONE', 'TWO', 'THREE', 'QOURUM', 'ALL', 'SERIAL',
                'LOCAL_QUORUM', 'EACH_QOURUM', 'LOCAL_SERIAL', 'LOCAL_ONE',
            ])) {
            $consistency = constant('\Cassandra::CONSISTENCY_'.strtoupper($config['consistency']));
            $builder->withDefaultConsistency($consistency);
        }
        if (array_key_exists('timeout', $config) && ! empty($config['timeout'])) {
            $builder->withDefaultTimeout(intval($config['timeout']));
        }
        if (array_key_exists('connect_timeout', $config) && ! empty($config['connect_timeout'])) {
            $builder->withConnectTimeout(floatval($config['connect_timeout']));
        }
        if (array_key_exists('request_timeout', $config) && ! empty($config['request_timeout'])) {
            $builder->withRequestTimeout(floatval($config['request_timeout']));
        }
        if (array_key_exists('username', $config) && array_key_exists('password', $config)) {
            $builder->withCredentials($config['username'], $config['password']);
        }

        return $builder->build()->connect($config['keyspace']);
    }

    /**
     * @return void
     */
    public function disconnect()
    {
        $this->connection->close();
        unset($this->connection);
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        return 'cassandra';
    }

    /**
     * @return \ShSo\Lacassa\Query\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new QueryProcessor();
    }

    /**
     * @return \ShSo\Lacassa\Query\Builder
     */
    protected function getDefaultQueryBuilder()
    {
        return new QueryBuilder($this, $this->getPostProcessor());
    }

    /**
     * @return \ShSo\Lacassa\Query\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar();
    }

    /**
     * @return \ShSo\Lacassa\Schema\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return new SchemaGrammar();
    }

    /**
     * Since it's not possible to find out the number
     * of affected rows through datastax driver,
     * it will check exceptions.
     *
     * @param $query string|\Cassandra\Statement
     * @param $bindings array
     *
     * @return int 1 on success and 0 on failure
     */
    public function affectingStatement($query, $bindings = [])
    {
        try {
            $this->statement($query, $bindings);

            return 1;
        } catch (CassandraRuntimeException $e) {
            return 0;
        }
    }

    /**
     * @param $query string|\Cassandra\Statement
     * @param $bindings array
     *
     * @return \Cassandra\Rows
     * @throws \Cassandra\Exception\RuntimeException
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $statement = $this->connection->prepare($query);
            $this->recordsHaveBeenModified();

            return $this->connection->execute($statement, ['arguments' => $bindings]);
        });
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (! isset($this->connection) || is_null($this->connection)) {
            $this->connection = $this->createConnection($this->config);
        }
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
