<?php

namespace ShSo\Lacassa\Query;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\Grammars\Grammar as BaseGrammar;

class Grammar extends BaseGrammar
{

    protected $selectComponents = [
        'columns',
        'from',
        'wheres',
        'limit',
        'allowFiltering',
    ];

    /**
      * Compile an insert statement into CQL.
      *
      * @param \ShSo\Lacassa\Query $query
      * @param array $values
      *
      * @return string
      */
    public function compileInsert(BaseBuilder $query, array $values)
    {
        // Essentially we will force every insert to be treated as a batch insert which
        // simply makes creating the CQL easier for us since we can utilize the same
        // basic routine regardless of an amount of records given to us to insert.
        $table = $this->wrapTable($query->from);

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $insertCollections = collect($query->bindings['insertCollection']);

        $insertCollectionArray = $insertCollections->mapWithKeys(function($collectionItem) {
            return [$collectionItem['column'] => $this->compileCollectionValues($collectionItem['type'], $collectionItem['value'])];
        })->all();

        $columns = $this->columnize(array_keys(reset($values)));
        $collectionColumns = $this->columnize(array_keys($insertCollectionArray));
        if ($collectionColumns) {
          $columns = $columns ? $columns .', '. $collectionColumns:$collectionColumns;
        }
        $collectionParam = $this->buildInsertCollectionParam($insertCollections);

        // We need to build a list of parameter place-holders of values that are bound
        // to the query. Each insert should have the exact same amount of parameter
        // bindings so we will loop through the record and parameterize them all.
        $parameters = collect($values)->map(function ($record) {
            return $this->parameterize($record);
        })->implode(', ');

        if ($collectionParam) {
          $parameters = $parameters ? $parameters .', '. $collectionParam : $collectionParam;
        }

        return "insert into {$table} ({$columns}) values ({$parameters})";
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     *
     * @return \Illuminate\Support\Collection
     */
    public function buildInsertCollectionParam($collection){
      return $collection->map(function($collectionItem) {
        return $this->compileCollectionValues($collectionItem['type'], $collectionItem['value']);
      })->implode(', ');
    }

    /**
     * Wrap a single string in keyword identifiers.
     *
     * @param string $value
     *
     * @return string
     */
    protected function wrapValue($value)
    {
        if ($value !== '*') {
            return str_replace('"', '""', $value);
        }

        return $value;
    }

    /**
     * Compile a delete statement into CQL.
     *
     * @param \ShSo\Lacassa\Query $query
     *
     * @return string
     */
    public function compileDelete(BaseBuilder $query)
    {
        $delColumns = "";
        if (isset($query->delParams)) {
            $delColumns = implode(", ", $query->delParams);
        }

        $wheres = is_array($query->wheres) ? $this->compileWheres($query) : '';
        return trim("delete ".$delColumns." from {$this->wrapTable($query->from)} $wheres");
    }

    /**
     * Compile an update statement into SQL.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param array $values
     *
     * @return string
     */
    public function compileUpdate(BaseBuilder $query, $values)
    {
        $table = $this->wrapTable($query->from);
        // Each one of the columns in the update statements needs to be wrapped in the
        // keyword identifiers, also a place-holder needs to be created for each of
        // the values in the list of bindings so we can make the sets statements.
        $columns = collect($values)->map(
            function ($value, $key) {
                return $this->wrap($key).' = '.$this->parameter($value);
            }
        )->implode(', ');

        // Of course, update queries may also be constrained by where clauses so we'll
        // need to compile the where clauses and attach it to the query so only the
        // intended records are updated by the SQL statements we generate to run.
        $wheres = $this->compileWheres($query);
        $upateCollections = $this->compileUpdateCollections($query);
        if ($upateCollections) {
          $upateCollections = $columns ? ', '.$upateCollections : $upateCollections;
        }

        return trim("update {$table} set $columns $upateCollections $wheres");
    }

    /**
     * Compiles the udpate collection methods
     *
     * @param BaseBuilder $query
     *
     * @return string
     */
    public function compileUpdateCollections(BaseBuilder $query)
    {
        $updateCollections = collect($query->bindings['updateCollection']);

        $updateCollectionCql = $updateCollections->map(function ($collection, $key) {
            if ($collection['operation']) {
                return $collection['column'] . '=' . $collection['column'] . $collection['operation'] . $this->compileCollectionValues($collection['type'], $collection['value']);
            } else {
                return $collection['column'] . '=' . $this->compileCollectionValues($collection['type'], $collection['value']);
            }
        })->implode(', ');
        return $updateCollectionCql;

    }

    /**
     * Compiles the values assigned to collections
     *
     * @param string $type
     * @param string $value
     *
     * @return string
     */
    public function compileCollectionValues($type, $value)
    {
        if (is_array($value)) {

            if ('set' == $type) {
                $collection = "{".$this->buildCollectionString($type, $value)."}";
            } elseif ('list' == $type) {
                $collection = "[".$this->buildCollectionString($type, $value)."]";
            } elseif ('map' == $type) {
                $collection = "{".$this->buildCollectionString($type, $value)."}";
            }

            return $collection;
        }

    }

    /**
     * Builds the insert string
     *
     * @param string $type
     * @param string $value
     *
     * @return string
     */
    public function buildCollectionString($type, $value)
    {

        $items = [];
        if ($type === 'map') {
            foreach ($value as $item) {
                list($key, $value, $qoutk, $qoutv) = [$item[0], $item[1], $item['key'] ?? null, $item['value'] ?? null];

                if (!is_bool($qoutk)) {
                    $qoutk = 'string' == strtolower(gettype($key));
                }

                if (!is_bool($qoutv)) {
                    $qoutv = 'string' == strtolower(gettype($value));
                }

                $key = $qoutk ? "'{$key}'" : $key;
                $value = $qoutv ? "'{$value}'" : $value;
                $items[] = "{$key}:{$value}";
            }
        } elseif ($type === 'set' || $type === 'list') {
            foreach ($value as $item) {
                $qoutv = 'string' == strtolower(gettype($item));
                $items[] = $qoutv ? "'{$item}'" : $item;
            }
        }

        return implode(',', $items);
    }

    /**
     * @param Builder $query
     * @param string $columns
     *
     * @return string
     */
    public function compileIndex($query, $columns)
    {
      $table = $this->wrapTable($query->from);
      $value = implode(", ",$columns);
      return "CREATE INDEX IF NOT EXISTS ON ". $table ."(".  $value .")";
    }

    public function compileAllowFiltering($query, $allow_filtering)
    {
        return $allow_filtering ? 'allow filtering':'';
    }
}

