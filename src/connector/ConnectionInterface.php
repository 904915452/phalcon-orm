<?php
namespace Dm\PhalconOrm\connector;

use Dm\PhalconOrm\BaseQuery;
use Dm\PhalconOrm\DbManager;

/**
 * Connection interface.
 */
interface ConnectionInterface
{
    /**
     * 获取当前连接器类对应的Query类.
     * @return string
     */
    public function getQueryClass(): string;

    /**
     * 指定表名开始查询.
     * @param $table
     * @return BaseQuery
     */
    public function table($table);

    /**
     * 指定表名开始查询(不带前缀).
     * @param $name
     * @return BaseQuery
     */
    public function name($name);

    /**
     * 设置当前的数据库Db对象
     * @param DbManager $db
     * @return void
     */
    public function setDb(DbManager $db);

    /**
     * 查找单条记录.
     * @param BaseQuery $query 查询对象
     * @return array
     */
    public function first(BaseQuery $query): array;
//
//    /**
//     * 查找记录.
//     * @param BaseQuery $query 查询对象
//     * @return array
//     */
//    public function get(BaseQuery $query): array;
//
//    /**
//     * 批量插入记录.
//     * @param BaseQuery $query   查询对象
//     * @param mixed     $dataSet 数据集
//     * @return int
//     */
//    public function insertAll(BaseQuery $query, array $dataSet = []): int;
//
//    /**
//     * 得到某个字段的值
//     * @param BaseQuery $query   查询对象
//     * @param string    $field   字段名
//     * @param mixed     $default 默认值
//     *
//     * @return mixed
//     */
//    public function value(BaseQuery $query, string $field, $default = null);
//
//    /**
//     * 得到某个列的数组.
//     * @param BaseQuery    $query  查询对象
//     * @param string|array $column 字段名 多个字段用逗号分隔
//     * @param string       $key    索引
//     * @return array
//     */
//    public function column(BaseQuery $query, string|array $column, string $key = ''): array;
//
//    /**
//     * 执行数据库事务
//     * @param callable $callback 数据操作方法回调
//     * @return mixed
//     */
//    public function transaction(callable $callback);
//
//    /**
//     * 启动事务
//     * @return void
//     */
//    public function startTrans();
//
//    /**
//     * 用于非自动提交状态下面的查询提交.
//     * @return void
//     */
//    public function commit();
//
//    /**
//     * 事务回滚.
//     * @return void
//     */
//    public function rollback();
//
//    /**
//     * 取得数据表的字段信息.
//     * @param string $tableName
//     * @return array
//     */
//    public function getTableFields(string $tableName): array;
//
//    /**
//     * 获取最近一次查询的sql语句.
//     * @return string
//     */
//    public function getLastSql(): string;
//
//    /**
//     * 获取最近插入的ID.
//     * @param BaseQuery $query    查询对象
//     * @param string    $sequence 自增序列名
//     * @return mixed
//     */
//    public function getLastInsID(BaseQuery $query, string $sequence = null);
}
