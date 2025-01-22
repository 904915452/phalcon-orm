<?php
namespace Dm\PhalconOrm\connector;

use Dm\PhalconOrm\BaseBuilder;
use Dm\PhalconOrm\BaseQuery;
use Dm\PhalconOrm\Builder;
use Dm\PhalconOrm\DbManager;

trait ConnectionTrait {

    /**
     * 数据表信息.
     *
     * @var array
     */
    protected $info = [];

    /**
     * 指定表名开始查询.
     * @param $table
     * @return BaseQuery
     */
    public function table($table)
    {
        return $this->newQuery()->table($table);
    }

    /**
     * 指定表名开始查询(不带前缀).
     * @param $name
     * @return BaseQuery
     */
    public function name($name)
    {
        return $this->newQuery()->name($name);
    }

    /**
     * 创建查询对象
     */
    public function newQuery()
    {
        $class = $this->getQueryClass();

        /** @var BaseQuery $query */
        $query = new $class($this);

        $timeRule = $this->db->getConfig('time_query_rule');
        if (!empty($timeRule)) {
//            $query->timeRule($timeRule);
        }

        return $query;
    }

    /**
     * 设置当前的数据库Db对象
     * @param DbManager $db
     * @return void
     */
    public function setDb(DbManager $db)
    {
        $this->db = $db;
    }

    /**
     * 获取当前的builder实例对象
     * @return BaseBuilder
     */
    public function getBuilder()
    {
        return $this->builder;
    }

    /**
     * 获取最终的SQL语句.
     *
     * @param string $sql  带参数绑定的sql语句
     * @param array  $bind 参数绑定列表
     *
     * @return string
     */
    public function getRealSql(string $sql, array $bind = []): string
    {
        foreach ($bind as $key => $val) {
            $value = strval(is_array($val) ? $val[0] : $val);
            $type  = is_array($val) ? $val[1] : self::PARAM_STR;

            if (self::PARAM_FLOAT == $type || self::PARAM_STR == $type) {
                $value = '\'' . addslashes($value) . '\'';
            } elseif (self::PARAM_INT == $type && '' === $value) {
                $value = '0';
            }

            // 判断占位符
            $sql = is_numeric($key) ?
                substr_replace($sql, $value, strpos($sql, '?'), 1) :
                substr_replace($sql, $value, strpos($sql, ':' . $key), strlen(':' . $key));
        }

        return rtrim($sql);
    }
}