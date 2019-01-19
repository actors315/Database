# Twinkle\Database

支持所有原生PDO功能，并作了相应的扩展。

1.支持懒连接  
2.提供 Connection 管理主从  
3.提供简单的 Query builder  

[![Build Status](https://travis-ci.org/yumancang/Database.svg?branch=master)](https://travis-ci.org/yumancang/Database)

**单实例**

```
$db = new \Twinkle\Database\DB([
    "dsn" => "mysql:host=127.0.0.1;port=3306;dbname=db_demo",
    "username" => 'test',
    "password" => 'test_pass'
]);
$info = $db->execQuery(
    (new \Twinkle\Database\Query())->select('user_id,email')
        ->from('user_info')
        ->where('user_id = ?',1992)
        ->limit(1)
)->fetchInto();
```

**主从实例**

```
$master = function () {
    return new \Twinkle\Database\DB([
        "dsn" => "mysql:host=master.test.db;port=3306;dbname=db_demo",
        "username" => 'master_user',
        "password" => 'master_pass'
    ]);
};

$slave1 = function () {
    return new \Twinkle\Database\DB([
        "dsn" => "mysql:host=slave1.test.db;port=3306;dbname=db_demo",
        "username" => 'slave1_test',
        "password" => 'slave1_pass'
    ]);
};

$slave2 = function () {
    return new \Twinkle\Database\DB([
        "dsn" => "mysql:host=slave2.test.db;port=3306;dbname=db_demo",
        "username" => 'slave2_test',
        "password" => 'slave2_pass'
    ]);
};

$connection = new \Twinkle\Database\Connection();
$connection->setWrite($master);
$connection->setRead('slave1',$slave1);
$connection->setRead('slave2',$slave2);

$info = $connection->getRead()->execQuery(
    (new \Twinkle\Database\Query())->select('user_id,email')
        ->from('user_info')
        ->where('user_id = ?',1992)
        ->limit(1)
)->fetchInto();
```