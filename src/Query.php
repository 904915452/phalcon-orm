<?php
namespace Dm\PhalconOrm;

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
}