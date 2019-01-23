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
        if (!extension_loaded('pdo_mysql')) {
            $this->markTestSkipped("Need 'pdo_mysql' extension to test mysql.");
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
            'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=twinkle_test',
            'username' => 'root',
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
            $this->db->insert('db_test',['name' => $name]);
        }
    }

    public function testInsert()
    {
        $stm = $this->db->insert('db_test',['name' => 'testInsert']);
        $affectedCount = $stm->rowCount();
        $this->assertEquals(1, $affectedCount);
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