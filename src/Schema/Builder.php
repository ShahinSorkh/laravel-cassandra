<?php

namespace ShSo\Lacassa\Schema;

use Closure;
use Illuminate\Database\Schema\Builder as BaseBuilder;
use ShSo\Lacassa\Connection;

class Builder extends BaseBuilder
{
    /**
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->grammar = $connection->getSchemaGrammar();
    }

    /**
     * @return \ShSo\Lacassa\Schema\Builder
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        return new Blueprint($this->connection, $table);
    }
}
