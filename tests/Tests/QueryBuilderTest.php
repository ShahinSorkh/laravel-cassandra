<?php

namespace ShSo\Lacassa\Tests;

use Cassandra\FutureRows as CassandraFutureRows;
use Cassandra\Rows as CassandraRows;
use Cassandra\Timestamp as CassandraTimestamp;
use Cassandra\Uuid as CassandraUuid;
use DB;
use Faker\Factory as FakerFactory;
use ShSo\Lacassa\Query\Builder as QueryBuilder;
use ShSo\Lacassa\TestCase;

class QueryBuilderTest extends TestCase
{
    public function testNewBuilder()
    {
        $connection = DB::connection('cassandra');
        $this->assertInstanceOf(QueryBuilder::class, new QueryBuilder($connection));
        $this->assertInstanceOf(QueryBuilder::class, $connection->table('foo'));
    }

    public function testFrom()
    {
        $connection = DB::connection('cassandra');
        $this->assertEquals(
            (new QueryBuilder($connection))->from('foo'),
            $connection->table('foo')
        );
    }

    public function testDistinct()
    {
        $builder = DB::table('foo');
        $this->assertFalse($builder->distinct);
        $builder->distinct();
        $this->assertTrue($builder->distinct);
    }

    public function testAllowFiltering()
    {
        $builder = DB::table('foo');
        $this->assertFalse($builder->allowFiltering);
        $builder->allowFiltering();
        $this->assertTrue($builder->allowFiltering);
    }

    public function testGet()
    {
        $builder = DB::table('users');
        $this->assertInstanceOf(CassandraRows::class, $builder->get());
        $this->assertInstanceOf(CassandraFutureRows::class, $builder->getAsync());
    }

    public function testCount()
    {
        \Config::set('database.connections.cassandra.page_size', 2);
        $next_century = date('Y-m', strtotime('+100 years'));
        $builder = DB::table('posts_by_month')->where('published_month', $next_century);

        $this->assertEquals(0, $builder->count());
        DB::execute('insert into posts (user, id, published_month) values (?,?,?)', ['arguments' => [new CassandraUuid(), new CassandraUuid(), $next_century]]);
        $this->assertEquals(1, $builder->count());
        DB::execute('insert into posts (user, id, published_month) values (?,?,?)', ['arguments' => [new CassandraUuid(), new CassandraUuid(), $next_century]]);
        DB::execute('insert into posts (user, id, published_month) values (?,?,?)', ['arguments' => [new CassandraUuid(), new CassandraUuid(), $next_century]]);
        DB::execute('insert into posts (user, id, published_month) values (?,?,?)', ['arguments' => [new CassandraUuid(), new CassandraUuid(), $next_century]]);
        $this->assertEquals(4, $builder->count());

        $results = DB::execute('select published_month,user,id from posts_by_month where published_month=?', ['arguments' => [$next_century]]);
        if ($results->count()) {
            while (true) {
                foreach ($results as $row) {
                    DB::execute('delete from posts where user=? and id=?', ['arguments' => [$row['user'], $row['id']]]);
                }
                if ($results->isLastPage()) {
                    break;
                }
                $results = $results->nextPage();
            }
        }

        $this->assertEquals(0, $builder->count());
    }

    public function testDeletes()
    {
        $next_century = strtotime('+101 years +'.rand(10, 40000).'minutes');
        $faker = FakerFactory::create();

        $this->assertEquals(0, DB::table('posts_by_month')->where('published_month', date('Y-m', $next_century))->count());

        $titles = $users = $post_ids = [];
        foreach (range(1, 10) as $i) {
            $users[$i] = new CassandraUuid();
            $post_ids[$i] = new CassandraUuid();
            $titles[$i] = $faker->sentence(3);
            DB::execute('insert into posts (user, id, published_month, published_at, title) values (?,?,?,?,?)', ['arguments' => [$users[$i], $post_ids[$i], date('Y-m', $next_century), new CassandraTimestamp($next_century), $titles[$i]]]);
        }

        $this->assertEquals(10, DB::table('posts_by_month')->where('published_month', date('Y-m', $next_century))->count());

        $random_index = array_rand($users);
        $builder = DB::table('posts')
            ->where('user', $users[$random_index])
            ->where('id', $post_ids[$random_index]);
        $this->assertEquals($titles[$random_index], $builder->first()['title']);
        $builder->deleteColumn(['title'])->get();
        $this->assertNull($builder->first()['title']);

        foreach ($users as $i => $user) {
            DB::table('posts')->where('user', $user)->where('id', $post_ids[$i])->deleteRow()->get();
        }
        $this->assertEquals(0, DB::table('posts_by_month')->where('published_month', date('Y-m', $next_century))->count());
    }
}
