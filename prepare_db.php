<?php

require './vendor/autoload.php';

$session = Cassandra::cluster()->build()->connect();

echo 'creating testing keyspace...'.PHP_EOL;
$session->execute("CREATE KEYSPACE IF NOT EXISTS testing WITH replication = {'class': 'SimpleStrategy', 'replication_factor': '1'}  AND durable_writes = true;");
echo 'creating table users...'.PHP_EOL;
$session->execute('CREATE TABLE IF NOT EXISTS testing.users ( id uuid primary key, username text, email text, password text, birthdate date )');
$session->executeAsync('CREATE MATERIALIZED VIEW IF NOT EXISTS testing.users_by_username AS SELECT * FROM testing.users WHERE username IS NOT NULL AND id IS NOT NULL PRIMARY KEY (username, id)');
$session->executeAsync('CREATE MATERIALIZED VIEW IF NOT EXISTS testing.users_by_email AS SELECT * FROM testing.users WHERE email IS NOT NULL AND id IS NOT NULL PRIMARY KEY (email, id)');

echo 'creating table posts...'.PHP_EOL;
$session->execute('CREATE TABLE IF NOT EXISTS testing.posts ( user uuid, id uuid, title text, body text, published_at timestamp, published_month text, primary key (user, id) )');
$session->executeAsync('CREATE MATERIALIZED VIEW IF NOT EXISTS testing.posts_by_month AS SELECT * FROM testing.posts WHERE user IS NOT NULL AND id IS NOT NULL AND published_month IS NOT NULL PRIMARY KEY (published_month, user, id)');

echo 'truncating possible existing data...'.PHP_EOL;
$session->execute('TRUNCATE testing.users');
$session->execute('TRUNCATE testing.posts');

$faker = Faker\Factory::create();

echo 'inserting users...'.PHP_EOL;
$p = $session->prepare('insert into testing.users (id, username, email, password, birthdate) values (?,?,?,?,?)');
$users_passwords = [];
$count_users = 0;
foreach (range(0, 10) as $i) {
    $new_id = new Cassandra\Uuid();
    $password = $faker->password;
    $users_passwords[$new_id->uuid()] = $password;
    $session->executeAsync($p, ['arguments' => [
        $new_id,
        $faker->username,
        $faker->email,
        password_hash($password, PASSWORD_DEFAULT),
        new Cassandra\Date(strtotime($faker->date)),
    ]]);
    $count_users++;
}

echo 'dumping users...'.PHP_EOL;
$users_file = 'tests/data/users.json';
file_put_contents($users_file, json_encode($users_passwords));

echo 'inserting posts...'.PHP_EOL;
$p = $session->prepare('insert into testing.posts (user, id, title, body, published_at, published_month) values (?,?,?,?,?,?)');
$count_posts = 0;
foreach (range(0, 200) as $i) {
    $published_at = $faker->unixTime;
    $session->executeAsync($p, ['arguments' => [
        new Cassandra\Uuid(array_rand($users_passwords)),
        new Cassandra\Uuid(),
        $faker->sentence(3),
        $faker->realText(20000),
        new Cassandra\Timestamp($published_at),
        date('Y-m', $published_at),
    ]]);
    $count_posts++;
}

echo 'dumping stats...'.PHP_EOL;
$data_file = 'tests/data/data.json';
$data = [
    'users' => $count_users,
    'posts' => $count_posts,
];
file_put_contents($data_file, json_encode($data));

echo 'all users ids and their respective passwords are saved to tests/data/users.json'.PHP_EOL;
