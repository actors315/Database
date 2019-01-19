<?php

namespace Twinkle\Database;

/**
 * Created by PhpStorm.
 * User: huanjin
 * Date: 2019/1/19
 * Time: 23:22
 */

/**
 * Class DBTest
 * @package Twinkle\Database
 */
class DBTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var DB
     */
    protected $db;

    protected $data = [
        1 => 'Anna',
        2 => 'Betty',
        3 => 'Clara',
        4 => 'Donna',
        5 => 'Fiona',
        6 => 'Gertrude',
        7 => 'Hanna',
        8 => 'Ione',
        9 => 'Julia',
        10 => 'Kara',
    ];

    public function setUp()
    {
        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped("Need 'pdo_sqlite' to test in memory.");
        }
        $this->db = $this->newDb();
        $this->createTable();
        $this->fillTable();
    }

    /**
     * @return DB
     */
    protected function newDb()
    {
        return new DB([
            'dsn' => 'sqlite::memory:',
        ]);
    }

    protected function createTable()
    {
        $sql = "CREATE TABLE db_test (
            id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(10) NOT NULL
        )";
        $this->db->execQueryString($sql);
    }

    protected function fillTable()
    {
        foreach ($this->data as $id => $name) {
            $this->insert(['name' => $name]);
        }
    }

    protected function insert(array $data)
    {
        $cols = array_keys($data);
        $vals = [];
        foreach ($cols as $col) {
            $vals[] = ":$col";
        }
        $cols = implode(', ', $cols);
        $vals = implode(', ', $vals);
        $sql = "INSERT INTO db_test ({$cols}) VALUES ({$vals})";
        $this->db->execQueryString($sql, $data);
    }

    public function testExecQuery()
    {
        $stm = $this->db->execQuery(
            (new Query())->select('id,name')->from('db_test')
        );
        $this->assertInstanceOf(Statement::class, $stm);
        $result = $stm->fetchAll();
        $expect = 10;
        $actual = count($result);
        $this->assertEquals($expect, $actual);
    }

}