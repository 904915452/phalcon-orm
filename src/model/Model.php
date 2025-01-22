<?php

namespace Dm\PhalconOrm\model;

use Dm\PhalconOrm\DbManager;
use Dm\PhalconOrm\Query;
use Phalcon\Mvc\Model as MvcModel;

abstract class Model extends MvcModel
{
    /**
     * 数据库管理类
     * @var DbManager
     */
    protected static DbManager $db;

    /**
     * 主键
     * @var string
     */
    protected string $pk = 'id';

    /**
     * 数据表后缀
     *
     * @var string
     */
    protected string $suffix;

    public function initialize(): void
    {
        self::setDb(new DbManager());
    }

    public static function setDb(DbManager $db): void
    {
        self::$db = $db;
    }

    public function db(): Query
    {
        $query = self::$db->setConnector($this->getWriteConnection())
            ->name($this->getSource())
            ->pk($this->pk);

        $query->model($this);

        return $query;
    }

    public function __call($method, $arguments)
    {
        return call_user_func_array([$this->db(), $method], $arguments);
    }

    public static function __callStatic($method, $arguments)
    {
        $model = new static();
        return call_user_func_array([$model->db(), $method], $arguments);
    }
}