<?php
/**
 * Created by PhpStorm.
 * User: xiehuanjin
 * Date: 2019/1/13
 * Time: 15:07
 */

namespace Twinkle\Database;

/**
 * 一般情况下，优秀的项目都不只一个db。
 * 比如一主多从
 *
 * Class Connection
 * @package Twinkle\Database
 */
class Connection
{

    /**
     *
     * A registry of PDO connection entries.
     *
     * @var array
     *
     */
    protected $registry = [
        'write' => null,
        'read' => [

        ]
    ];

    public function __construct($master = null, $slaves = [])
    {
        $this->setWrite($master);
        foreach ($slaves as $key => $slave) {
            $this->setRead($key, $slave);
        }
    }

    public function setWrite($callable)
    {
        $this->registry['write'] = $callable;
    }

    public function setRead($index, $callable)
    {
        $this->registry['read'][$index] = $callable;
    }

    /**
     * @return DB
     */
    public function getWrite()
    {
        return $this->getConnection('write');
    }

    /**
     * @param null | string $index
     * @return DB
     */
    public function getRead($index = null)
    {
        return $this->getConnection('read', $index);
    }

    /**
     * @param $type
     * @param null $index
     * @return DB
     * @throws \Exception
     */
    protected function getConnection($type, $index = null)
    {
        if (!isset($this->registry[$type])) {
            throw new \Exception('数据库连接不存在');
        }

        if ('write' == $type) {
            $connection = $this->registry[$type];
        } elseif ('read' == $type) {
            if (empty($this->registry[$type])) {
                $connection = $this->registry['write'];
            } elseif (null == $index) {
                $index = array_rand($this->registry[$type]);
                $connection = $this->registry[$type][$index];
            } else {
                $connection = $this->registry[$type][$index];
            }
        } else {
            throw new \Exception('数据库连接不存在');
        }

        if ($connection instanceof DB) {
            return $connection;
        }

        $connection = call_user_func($connection);
        if ('write' == $type) {
            $this->setWrite($connection);
        } elseif ('read' == $type) {
            $this->setRead($index, $connection);
        }

        return $connection;
    }

}