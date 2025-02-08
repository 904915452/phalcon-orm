<?php
namespace Dm\PhalconOrm;

use Dm\PhalconOrm\concern\JoinAndViewQuery;
use Dm\PhalconOrm\concern\ParamsBind;
use Dm\PhalconOrm\concern\TableFieldInfo;
use Exception;

/**
 * PDO数据查询类.
 */

class Query extends BaseQuery
{
    use ParamsBind;
    use TableFieldInfo;
    use JoinAndViewQuery;

    /**
     *  当前数据表自增主键.
     * @var string
     * /
     */
    protected $autoinc;

    /**
     * 创建子查询SQL.
     * @param bool $sub 是否添加括号
     * @throws Exception
     * @return string
     */
    public function buildSql(bool $sub = true): string
    {
        return $sub ? '( ' . $this->fetchSql()->select() . ' )' : $this->fetchSql()->select();
    }

    /**
     * 获取执行的SQL语句而不进行实际的查询.
     * @param bool $fetch 是否返回sql
     * @return $this|Fetch
     */
    public function fetchSql(bool $fetch = true)
    {
        $this->options['fetch_sql'] = $fetch;
        if ($fetch) {
            return new Fetch($this);
        }
        return $this;
    }

    /**
     * 表达式方式指定查询字段.
     * @param string $field 字段名
     * @return $this
     */
    public function fieldRaw(string $field)
    {
        $this->options['field'][] = new Raw($field);
        return $this;
    }

    /**
     * 表达式方式指定Field排序.
     * @param string $field 排序字段
     * @param array  $bind  参数绑定
     * @return $this
     */
    public function orderRaw(string $field, array $bind = [])
    {
        $this->options['order'][] = new Raw($field, $bind);
        return $this;
    }

    /**
     * 指定group查询.
     * @param string|array $group GROUP
     * @return $this
     */
    public function group($group)
    {
        $this->options['group'] = $group;
        return $this;
    }

    /**
     * 指定having查询.
     * @param string $having having
     * @return $this
     */
    public function having(string $having)
    {
        $this->options['having'] = $having;
        return $this;
    }

    /**
     * 指定distinct查询.
     * @param bool $distinct 是否唯一
     * @return $this
     */
    public function distinct(bool $distinct = true)
    {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /**
     * 设置当前查询所在的分区.
     * @param string|array $partition 分区名称
     * @return $this
     */
    public function partition($partition)
    {
        $this->options['partition'] = $partition;
        return $this;
    }

    /**
     * 获取当前数据表的自增主键.
     *
     * @return string|null
     */
    public function getAutoInc()
    {
        $tableName = $this->getTable();
        if (empty($this->autoinc) && $tableName) {
            $this->autoinc = $this->connection->getAutoInc($tableName);
        }
        return $this->autoinc;
    }

    /**
     * 设置DUPLICATE.
     * @param array|string|Raw $duplicate DUPLICATE信息
     * @return $this
     */
    public function duplicate($duplicate)
    {
        $this->options['duplicate'] = $duplicate;
        return $this;
    }

    /**
     * 设置是否REPLACE.
     * @param bool $replace 是否使用REPLACE写入数据
     * @return $this
     */
    public function replace(bool $replace = true)
    {
        $this->options['replace'] = $replace;
        return $this;
    }

}