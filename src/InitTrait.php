<?php
/**
 * Created by PhpStorm.
 * User: xiehuanjin
 * Date: 2019/1/13
 * Time: 14:53
 */

namespace Twinkle\Database;


trait InitTrait
{

    public function init($config)
    {
        $r = new \ReflectionClass($this);
        $ex = $this->getExcludeInitProperty();
        foreach ($config as $p => $v) {
            if (in_array($p, $ex)) continue;

            if (($method = $this->hasSet($p)) !== false) {
                $this->$method($p, $v);
                continue;
            }

            if ($r->getProperty($p)->isPublic()) {
                $this->{$p} = $v;
                continue;
            }

            throw new \Exception('Property `' . $p . '` not exists');
        }
    }

    protected function hasSet($key)
    {
        $key = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $key);

        $method = "set{$key}";

        if (method_exists($this, $method)) {
            return $method;
        }

        return false;
    }

    /**
     * 非构函数传入的参数
     * @return array
     */
    protected function getExcludeInitProperty()
    {
        return [];
    }
}