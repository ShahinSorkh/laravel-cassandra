Lacassa
=======

## WORKING ON A NEW VERSION WAIT FOR DOCUMENTATION UPDATES

A Query builder with support for Cassandra, using the original Laravel API.
This library extends the original Laravel classes, so it uses exactly the same methods.

## **Table of contents**

* Installation

* Configuration

* Query Builder

* Schema

* Extensions

* Examples

## **Installation**

Make sure you have the DataStax PHP Driver for Apache Cassandra installed.
You can find installation instructions at https://github.com/datastax/php-driver or
https://github.com/datastax/php-driver/blob/master/ext/README.md

Note: _datastax php-driver works with php version 5.6.\*, 7.0.\* and 7.1.\* only_

Installation using composer:

```sh
composer require shso/laravel-cassandra
```

And add the service provider in config/app.php:

```php
# config/app.php
...
providers: [
    ...,
    ShSo\Lacassa\CassandraServiceProvider::class,
    ...,
],
...
```

## **Configuration**

Change your default database connection name in config/database.php:

```php
# config/database.php
    'default' => env('DB_CONNECTION', 'cassandra'),
```

And add a new cassandra connection:

```php
# config/database.php
    'cassandra' => [
        'driver' => 'Cassandra',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 7000),
        'keyspace' => env('DB_DATABASE', 'cassandra_db'),
        'username' => env('DB_USERNAME', ''),
        'password' => env('DB_PASSWORD', ''),
        'page_size' => '5000',
        'consistency' => 'local_one',
        'timeout' => null,
        'connect_timeout' => 5.0,
        'request_timeout' => 12.0,
    ],
```

_Note: you can enter all of your nodes like:_

```php
# .env
DB_HOST=192.168.100.140,192.168.100.141,192.168.100.142
```

Note: _you can choose one of the consistency levels below:_

|                 |                 |                 |                 |
|-----------------|-----------------|-----------------|-----------------|
| `any`           | `three`         | `local_qourum`  | `local_one`     |
| `one`           | `qourum`        | `each_qourum`   | `serial`        |
| `two`           | `all`           | `local_serial`  |                 |

**Query Builder**

The database driver plugs right into the original query builder.
When using cassandra connections, you will be able to build fluent queries to perform database operations.

```php
$emp = DB::table('emp')->get();
$emp = DB::table('emp')->where('emp_name', 'Christy')->first();
```

If you did not change your default database connection, you will need to specify it on each query.

```php
$emp = DB::connection('cassandra')->table('emp')->get();
```

**Examples**

### **Basic Usage**

**Retrieving All Records**

```php
$emp = DB::table('emp')->all();
```

**Indexing columns**

`CREATE INDEX` creates a new index on the given table for the named column.

```php
DB::table('users')->index(['name']);
```

**Selecting columns**

```php
$emp = DB::table('emp')->where('emp_no', '>', 50)->select('emp_name', 'emp_no')->get();
$emp = DB::table('emp')->where('emp_no', '>', 50)->get(['emp_name', 'emp_no']);
```

**Wheres**

The WHERE clause specifies which rows to query.
In the WHERE clause, refer to a column using the actual name, not an alias.
Columns in the WHERE clause need to meet one of these requirements:

* The partition key definition includes the column.

* A column that is indexed using `CREATE INDEX`.

```php
$emp = DB::table('emp')->where('emp_no', '>', 50)->take(10)->get();
```

**And Statements**

```php
$emp = DB::table('emp')->where('emp_no', '>', 50)->where('emp_name', '=', 'Christy')->get();
```

**Using Where In With An Array**

```php
$emp = DB::table('emp')->whereIn('emp_no', [12, 17, 21])->get();
```

**Order By**

ORDER BY clauses can select a single column only.
Ordering can be done in ascending or descending order, default ascending, and specified with the ASC or DESC keywords.
In the ORDER BY clause, refer to a column using the actual name, not the aliases.

```php
$emp = DB::table('emp')->where('emp_name', 'Christy')->orderBy('emp_no', 'desc')->get();
```

**Limit**

We can use limit() and take() for limiting the query.

```php
$emp = DB::table('emp')->where('emp_no', '>', 50)->take(10)->get();
$emp = DB::table('emp')->where('emp_no', '>', 50)->limit(10)->get();
```

**Distinct**

Distinct requires a field for which to return the distinct values.

```php
$emp = DB::table('emp')->distinct()->get(['emp_id']);
```

Distinct can be combined with **where**:

```php
$emp = DB::table('emp')->where('emp_sal', 45000)->distinct()->get(['emp_name']);
```

**Count**

```php
$number = DB::table('emp')->count();
```

Count can be combined with **where**:

```php
$sal = DB::table('emp')->where('emp_sal', 45000)->count();
```

**Truncate**

```php
$sal = DB::table('emp')->truncate();
```

### **Filtering a collection set, list, or map**

You can index the collection column, and then use the CONTAINS condition
in the WHERE clause to filter the data for a particular value in the collection.

```php
$emp = DB::table('emp')->where('emp_name', 'contains', 'Christy')->get();
```

After [indexing the collection keys](https://docs.datastax.com/en/cql/3.1/cql/cql_reference/create_index_r.html#reference_ds_eqm_nmd_xj__CreatIdxCollKey) in the venues map, you can filter on map keys.

```php
$emp = DB::table('emp')->where('todo', 'contains key', '2014-10-02 06:30:00+0000')->get();
```

**Raw Query**

The CQL expressions can be injected directly into the query.

```php
$emp = DB::raw('select * from emp');
```

**Inserts, updates and deletes**

Inserting, updating and deleting records works just like the original QB.

**Insert**

```php
DB::table('emp')
    ->insertCollection('set', 'phn', [123, 1234, 12345])
    ->insertCollection('map', 'friends', [['John', 'Male'], ['Eli', 'Female']])
    ->insert([
        'emp_id' => 11,
        'emp_name' => 'Christy',
        'emp_phone' => 12345676890,
        'emp_sal' => 500
    ]);
```

**Updating**

To update a model, you may retrieve it, change an attribute, and use the update method.

```php
DB::table('emp')
    ->where('emp_id', 11)
    ->update([
        'emp_city' => 'kochi',
        'emp_name' => 'Christy jos',
        'emp_phone' =>  123456789
    ]);
```

### **Updating a collection set, list, and map**

Update collections in a row. The method will be like

```php
updateCollection(collection_type, column_name, operator, value);
```

Collection\_type is any of set, list or map.

Column\_name is the name of column to be updated.

Operator is + or -, + for adding the values to collection and - to remove the value from collection.

Value can be associative array for map type and array of string/number for list and set types.

```php
DB::table('users')->where('id', 1)
    ->updateCollection('set', 'phn', '+', [123, 1234,12345])->update();

DB::table('users')->where('id', 1)
    ->updateCollection('set', 'phn', '-', [123])->update();

DB::table('users')->where('id', 1)
    ->updateCollection('list', 'hobbies', '+', ['reading', 'cooking', 'cycling'])->update();

DB::table('users')->where('id', 1)
    ->updateCollection('list', 'hobbies', '-', ['cooking'])->update();

DB::table('users')->where('id', 1)
    ->updateCollection('map', 'friends', '+', [['John', 'Male'], ['Rex', 'Male']])->update();

DB::table('users')->where('id', 1)
    ->updateCollection('map', 'friends', '-', ['John'])->update();
```

**Deleting**

To delete a model, simply call the delete method on the instance. We can delete the rows in a table by using deleteRow method:

```php
$emp = DB::table('emp')->where('emp_city', 'Kochi')->deleteRow();
```

We can also perform delete by the column in a table using deleteColumn method:

```php
$emp = DB::table('emp')->where('emp_id', 3)->deleteColumn();
```

