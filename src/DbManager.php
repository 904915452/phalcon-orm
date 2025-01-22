<?php

namespace Dm\PhalconOrm;

use Dm\PhalconOrm\model\Model;
use Phalcon\Db\Adapter\AdapterInterface;

class DbManager
{
    protected $connector;

    protected $config;

    /**
     * 架构函数.
     */
    public function __construct()
    {
        $this->modelMaker();
    }

    /**
     * 注入模型对象
     * @return void
     */
    protected function modelMaker(): void
    {
        Model::setDb($this);

//        if (is_object($this->event)) {
//            Model::setEvent($this->event);
//        }
//
//        Model::maker(function (Model $model) {
//            $isAutoWriteTimestamp = $model->getAutoWriteTimestamp();
//
//            if (is_null($isAutoWriteTimestamp)) {
//                // 自动写入时间戳
//                $model->isAutoWriteTimestamp($this->getConfig('auto_timestamp', true));
//            }
//
//            $dateFormat = $model->getDateFormat();
//
//            if (is_null($dateFormat)) {
//                // 设置时间戳格式
//                $model->setDateFormat($this->getConfig('datetime_format', 'Y-m-d H:i:s'));
//            }
//        });
    }

    /**
     * 设置数据库连接对象
     */
    public function setConnector($connector): Query
    {
        $this->connector = $connector;
        return $this->newQuery();
    }

    /*
     * 获取数据库连接对象
     */
    public function connect($config = []):Query
    {
        // 通过配置重新连接数据库 并创建query
        return $this->newQuery();
    }

    public function __call($method, $args)
    {
        return call_user_func_array([$this->connect(), $method], $args);
    }

    protected function newQuery(): Query
    {
        return new Query($this->connector);
    }
}
