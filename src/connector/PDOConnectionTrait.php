<?php

namespace Dm\PhalconOrm\connector;

use Closure;
use Dm\PhalconOrm\BaseQuery;
use Dm\PhalconOrm\exception\BindParamException;
use Dm\PhalconOrm\exception\DbException;
use Dm\PhalconOrm\exception\PDOException;
use PDO;
use PDOStatement;
use Throwable;

trait PDOConnectionTrait
{
    /**
     * pdo对象
     * @var \PDOStatement
     */
    protected $PDOStatement;

    /**
     * 影响行数
     * @var int
     */
    protected int $numRows;

    protected int $transTimes = 0;
    protected int $reConnectTimes = 0;

    /**
     * 字段属性大小写.
     * @var int
     */
    protected int $attrCase = PDO::CASE_LOWER;

    /**
     * 参数绑定类型映射.
     *
     * @var array
     */
    protected array $bindType = [
        'string' => self::PARAM_STR,
        'str' => self::PARAM_STR,
        'integer' => self::PARAM_INT,
        'int' => self::PARAM_INT,
        'boolean' => self::PARAM_BOOL,
        'bool' => self::PARAM_BOOL,
        'float' => self::PARAM_FLOAT,
        'datetime' => self::PARAM_STR,
        'timestamp' => self::PARAM_STR,
    ];

    /**
     * 执行查询 返回数据集.
     * @param BaseQuery $query 查询对象
     * @param mixed $sql sql指令
     * @return array
     * @throws DbException
     * @throws Throwable
     */
    protected function pdoQuery(BaseQuery $query, $sql): array
    {
        // 分析查询表达式
        $query->parseOptions();
        $bind = $query->getBind();

        if ($sql instanceof Closure) {
            $sql = $sql($query);
            $bind = $query->getBind();
        }

        $this->queryStr = $sql;
        $this->bind = $bind;

        $this->getPDOStatement($sql, $bind);

        return $this->getResult();
    }

    /**
     * 参数绑定
     * @param array $bind
     * @return void
     * @throws BindParamException
     */
    public function bindValue(array $bind)
    {
        foreach ($bind as $key => $val) {
            // 占位符
            $param = is_numeric($key) ? $key + 1 : ':' . $key;

            if (is_array($val)) {
                if (self::PARAM_INT == $val[1] && '' === $val[0]) {
                    $val[0] = 0;
                } elseif (self::PARAM_FLOAT == $val[1]) {
                    $val[0] = is_string($val[0]) ? (float)$val[0] : $val[0];
                    $val[1] = self::PARAM_STR;
                }

                $result = $this->PDOStatement->bindValue($param, $val[0], $val[1]);
            } else {
                $result = $this->PDOStatement->bindValue($param, $val);
            }

            if (!$result) {
                throw new BindParamException(
                    "Error occurred  when binding parameters '{$param}'",
                    [],
                    $this->getLastsql(),
                    $bind
                );
            }
        }
    }

    /**
     * 获取最近一次查询的sql语句.
     *
     * @return string
     */
    public function getLastSql(): string
    {
        return $this->getRealSql($this->queryStr, $this->bind);
    }

    /**
     * 获得数据集数组.
     * @return array
     */
    protected function getResult(): array
    {
        $result = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);
        $this->numRows = count($result);
        return $result;
    }

    /**
     * 执行查询但只返回PDOStatement对象
     *
     * @param string $sql sql指令
     * @param array $bind 参数绑定
     * @return PDOStatement|null
     * @throws DbException
     * @throws Throwable
     *
     */
    public function getPDOStatement(string $sql, array $bind = [])
    {
        try {
            // 记录SQL语句
            $this->queryStr = $sql;
            $this->bind = $bind;

            // 预处理
            $this->PDOStatement = $this->pdo->prepare($sql);

            // 参数绑定
            $this->bindValue($bind);

            // 执行查询
            $this->PDOStatement->execute();

            // SQL监控
//            if (!empty($this->config['trigger_sql'])) {
//                $this->trigger('', $master);
//            }

            $this->reConnectTimes = 0;

            return $this->PDOStatement;
        } catch (Throwable|\Exception $e) {
            if ($this->transTimes > 0) {
                // 事务活动中时不应该进行重试，应直接中断执行，防止造成污染。
//                if ($this->isBreak($e)) {
                // 尝试对事务计数进行重置
//                    $this->transTimes = 0;
//                }
                throw $e;
            }
            /*
                        else {
                            // if ($this->reConnectTimes < 4 && $this->isBreak($e)) {
                            var_dump($this->reConnectTimes);
                            if ($this->reConnectTimes < 4) {
                                $this->reConnectTimes++;
                                $this->close();
                                return $this->PDOStatement;
                            }
                        }*/

            if ($e instanceof \PDOException) {
                throw new PDOException($e, [], $this->getLastsql());
            } else {
                throw $e;
            }
        }
    }

    /**
     * 释放查询结果.
     */
    public function free(): void
    {
        $this->PDOStatement = null;
    }

    /**
     * 获取数据表字段类型.
     * @param mixed $tableName 数据表名
     * @param string|null $field 字段名
     * @return array|string
     */
    public function getFieldsType($tableName, string $field = null)
    {
        $result = $this->getTableInfo($tableName, 'type');

        if ($field && isset($result[$field])) {
            return $result[$field];
        }

        return $result;
    }

    /**
     * 获取数据表信息.
     * @param mixed $tableName 数据表名 留空自动获取
     * @param string $fetch 获取信息类型 包括 fields type bind pk
     * @return mixed
     */
    public function getTableInfo(array|string $tableName, string $fetch = '')
    {
        if (is_array($tableName)) {
            $tableName = key($tableName) ?: current($tableName);
        }

        if (str_contains($tableName, ',') || str_contains($tableName, ')')) {
            // 多表不获取字段信息
            return [];
        }

        [$tableName] = explode(' ', $tableName);

        $info = $this->getSchemaInfo($tableName);

        return $fetch && isset($info[$fetch]) ? $info[$fetch] : $info;
    }

    /**
     * @param string $tableName 数据表名称
     * @return array
     */
    public function getSchemaInfo(string $tableName): array
    {
        // $schema = str_contains($tableName, '.') ? $tableName : $this->getConfig('database') . '.' . $tableName;// 8.0+
        $schema = strpos($tableName, '.') !== false ? $tableName : $this->getConfig('dbname') . '.' . $tableName;

        // 字段缓存 暂时去掉
        // $cacheKey = $this->getSchemaCacheKey($schema);
        $cacheKey = '';
        $info = $this->getCachedSchemaInfo($cacheKey, $tableName, true);

        $pk = $info['_pk'] ?? null;
        $autoinc = $info['_autoinc'] ?? null;
        unset($info['_pk'], $info['_autoinc']);

        $bind = array_map(fn($val) => $this->getFieldBindType($val), $info);

        $this->info[$schema] = [
            'fields' => array_keys($info),
            'type' => $info,
            'bind' => $bind,
            'pk' => $pk,
            'autoinc' => $autoinc,
        ];

        return $this->info[$schema];
    }

    /**
     * @param string $cacheKey 缓存key
     * @param string $tableName 数据表名称
     * @param bool $force 强制从数据库获取
     * @return array
     */
    protected function getCachedSchemaInfo(string $cacheKey, string $tableName, bool $force): array
    {
        return $this->getTableFieldsInfo($tableName);
    }

    /**
     * 获取字段绑定类型.
     * @param string $type 字段类型
     * @return int
     */
    public function getFieldBindType(string $type): int
    {
        /**
         * 8.0+
         */
        return match (true) {
            in_array($type, ['integer', 'string', 'float', 'boolean', 'bool', 'int', 'str']) => $this->bindType[$type],
            str_starts_with($type, 'set'), str_starts_with($type, 'enum') => self::PARAM_STR,
            preg_match('/(double|float|decimal|real|numeric)/i', $type) => self::PARAM_FLOAT,
            preg_match('/(int|serial|bit)/i', $type) => self::PARAM_INT,
            preg_match('/bool/i', $type) => self::PARAM_BOOL,
            default => self::PARAM_STR,
        };
    }

    /**
     * 获取数据表的字段信息.
     *
     * @param string $tableName 数据表名
     *
     * @return array
     */
    public function getTableFieldsInfo(string $tableName): array
    {
        $fields = $this->getFields($tableName);
        $info = [];

        foreach ($fields as $key => $val) {
            // 记录字段类型
            $info[$key] = $this->getFieldType($val['type']);

            if (!empty($val['primary'])) {
                $pk[] = $key;
            }

            if (!empty($val['autoinc'])) {
                $autoinc = $key;
            }
        }

        if (isset($pk)) {
            // 设置主键
            $pk = count($pk) > 1 ? $pk : $pk[0];
            $info['_pk'] = $pk;
        }

        if (isset($autoinc)) {
            $info['_autoinc'] = $autoinc;
        }

        return $info;
    }

    /**
     * 获取字段类型.
     * @param string $type 字段类型
     * @return string
     */
    protected function getFieldType(string $type): string
    {
        $type = strtolower($type);

        switch (true) {
            case preg_match('/(double|float|decimal|real|numeric)/i', $type):
                return 'float';
            case preg_match('/(int|serial|bit)/i', $type):
                return 'int';
            case strpos($type, 'bool') !== false:
                return 'bool';
            case strpos($type, 'timestamp') === 0:
                return 'timestamp';
            case strpos($type, 'datetime') === 0:
                return 'datetime';
            case strpos($type, 'date') === 0:
                return 'date';
            case strpos($type, 'set') === 0:
            case strpos($type, 'enum') === 0:
            default:
                return 'string';
        }
    }

    /**
     * 对返数据表字段信息进行大小写转换出来.
     * @param array $info 字段信息
     * @return array
     */
    public function fieldCase(array $info): array
    {
        // 字段大小写转换
        return match ($this->attrCase) {
            PDO::CASE_LOWER => array_change_key_case($info),
            PDO::CASE_UPPER => array_change_key_case($info, CASE_UPPER),
            PDO::CASE_NATURAL => $info,
            default => $info,
        };
    }

    /**
     * 获取数据表字段信息.
     * @param mixed $tableName 数据表名
     * @return array
     */
    public function getTableFields($tableName): array
    {
        return $this->getTableInfo($tableName, 'fields');
    }

    /**
     * 得到某个字段的值
     * @param BaseQuery $query 查询对象
     * @param string $field 字段名
     * @param mixed $default 默认值
     * @param bool $one 返回一个值
     * @return mixed
     * @throws DbException
     * @throws Throwable
     */
    public function value(BaseQuery $query, string $field, $default = null, bool $one = true)
    {
        $options = $query->parseOptions();

        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        if (isset($options['group'])) {
            $query->group('');
        }

        $query->setOption('field', (array)$field);

        // 生成查询SQL
        $sql = $this->builder->select($query, $one);

        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        if (isset($options['group'])) {
            $query->setOption('group', $options['group']);
        }

        // 执行查询操作
        $pdo = $this->getPDOStatement($sql, $query->getBind());
        $result = $pdo->fetchColumn();
        $result = false !== $result ? $result : $default;

        return $result;
    }

    /**
     * 得到某个列的数组.
     * @param BaseQuery $query 查询对象
     * @param string|array $column 字段名 多个字段用逗号分隔
     * @param string $key 索引
     * @return array
     * @throws DbException
     * @throws Throwable
     */
    public function column(BaseQuery $query, string|array $column, string $key = ''): array
    {
        $options = $query->parseOptions();

        if (isset($options['field'])) {
            $query->removeOption('field');
        }

        if (empty($key) || trim($key) === '') {
            $key = null;
        }

        if (is_string($column)) {
            $column = trim($column);
            if ('*' !== $column) {
                $column = array_map('trim', explode(',', $column));
            }
        } elseif (in_array('*', $column)) {
            $column = '*';
        }

        $field = $column;
        if ('*' !== $column && $key && !in_array($key, $column)) {
            $field[] = $key;
        }

        $query->setOption('field', (array)$field);

        // 生成查询SQL
        $sql = $this->builder->select($query);

        if (isset($options['field'])) {
            $query->setOption('field', $options['field']);
        } else {
            $query->removeOption('field');
        }

        // 执行查询操作
        $pdo = $this->getPDOStatement($sql, $query->getBind(), $options['master']);
        $resultSet = $pdo->fetchAll(PDO::FETCH_ASSOC);

        if (is_string($key) && str_contains($key, '.')) {
            [$alias, $key] = explode('.', $key);
        }

        if (empty($resultSet)) {
            $result = [];
        } elseif ('*' !== $column && count($column) === 1) {
            $column = array_shift($column);
            if (str_contains($column, ' ')) {
                $column = substr(strrchr(trim($column), ' '), 1);
            }

            if (str_contains($column, '.')) {
                [$alias, $column] = explode('.', $column);
            }

            if (str_contains($column, '->')) {
                $column = $this->builder->parseKey($query, $column);
            }

            $result = array_column($resultSet, $column, $key);
        } elseif ($key) {
            $result = array_column($resultSet, null, $key);
        } else {
            $result = $resultSet;
        }

        return $result;
    }

    /**
     * 插入记录.
     * @param BaseQuery $query 查询对象
     * @param bool $getLastInsID 返回自增主键
     * @return mixed
     * @throws DbException
     * @throws Throwable
     */
    public function newInsert(BaseQuery $query, bool $getLastInsID = false)
    {
        // 分析查询表达式
        $options = $query->parseOptions();

        // 生成SQL语句
        $sql = $this->builder->insert($query);

        // 执行操作
        $result = '' == $sql ? 0 : $this->pdoExecute($query, $sql);

        if ($result) {
            $sequence = $options['sequence'] ?? null;
            $lastInsId = $this->getLastInsID($query, $sequence);

            $data = $options['data'];

            if ($lastInsId) {
                $pk = $query->getAutoInc();
                if ($pk && is_string($pk)) {
                    $data[$pk] = $lastInsId;
                }
            }

            $query->setOption('data', $data);

//            $this->db->trigger('after_insert', $query);

            if ($getLastInsID && $lastInsId) {
                return $lastInsId;
            }
        }

        return $result;
    }

    /**
     * 更新记录.
     * @param BaseQuery $query 查询对象
     * @return int
     * @throws DbException
     * @throws Throwable
     */
    public function newUpdate(BaseQuery $query): int
    {
        $query->parseOptions();

        // 生成UPDATE SQL语句
        $sql = $this->builder->update($query);

        // 执行操作
        $result = '' == $sql ? 0 : $this->pdoExecute($query, $sql);

        if ($result) {
//            $this->db->trigger('after_update', $query);
        }
        return $result;
    }

    /**
     * 获取最近插入的ID.
     * @param BaseQuery $query 查询对象
     * @param string|null $sequence 自增序列名
     * @return mixed
     */
    public function getLastInsID(BaseQuery $query, string $sequence = null)
    {
        try {
            $insertId = $this->pdo->lastInsertId($sequence);
        } catch (\Exception $e) {
            $insertId = '';
        }

        return $this->autoInsIDType($query, $insertId);
    }

    /**
     * 获取最近插入的ID.
     * @param BaseQuery $query 查询对象
     * @param string $insertId 自增ID
     * @return mixed
     */
    protected function autoInsIDType(BaseQuery $query, string $insertId)
    {
        $pk = $query->getAutoInc();

        if ($pk && is_string($pk)) {
            $type = $this->getFieldsBind($query->getTable())[$pk];

            if (self::PARAM_INT == $type) {
                $insertId = (int)$insertId;
            } elseif (self::PARAM_FLOAT == $type) {
                $insertId = (float)$insertId;
            }
        }

        return $insertId;
    }

    /**
     * 获取数据表绑定信息.
     * @param mixed $tableName 数据表名
     * @return array
     */
    public function getFieldsBind($tableName): array
    {
        return $this->getTableInfo($tableName, 'bind');
    }

    /**
     * 执行语句.
     * @param BaseQuery $query 查询对象
     * @param string $sql sql指令
     * @param bool $origin 是否原生查询
     * @return int
     * @throws DbException
     * @throws Throwable
     */
    protected function pdoExecute(BaseQuery $query, string $sql, bool $origin = false): int
    {
        if ($origin) {
            $query->parseOptions();
        }

//        $this->queryPDOStatement($query->master(true), $sql);
        $this->queryPDOStatement($query, $sql);

        $this->numRows = $this->PDOStatement->rowCount();

        return $this->numRows;
    }

    /**
     * @param BaseQuery $query
     * @param string $sql
     * @return PDOStatement
     * @throws DbException|Throwable
     */
    protected function queryPDOStatement(BaseQuery $query, string $sql): PDOStatement
    {
        $bind = $query->getBind();
        return $this->getPDOStatement($sql, $bind);
    }

    /**
     * 获取数据表的自增主键.
     * @param mixed $tableName 数据表名
     * @return string
     */
    public function getAutoInc($tableName): string
    {
        return $this->getTableInfo($tableName, 'autoinc');
    }

    /**
     * 执行数据库事务
     * @param callable $callback 数据操作方法回调
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     *
     * @throws PDOException
     */
    public function transaction(callable $callback)
    {
        $this->begin();

        try {
            $result = $callback($this);

            $this->commit();

            return $result;
        } catch (\Exception|\Throwable $e) {
            $this->rollback();

            throw $e;
        }
    }

    /**
     * 启动事务
     * @return void
     * @throws \Exception
     * @throws \PDOException
     */
    public function startTrans(): void
    {
        $this->begin();
    }

    /**
     * 批量插入记录.
     * @param BaseQuery $query   查询对象
     * @param array     $dataSet 数据集
     * @throws \Exception
     * @throws \Throwable
     * @return int
     */
    public function insertAll(BaseQuery $query, array $dataSet = []): int
    {
        if (!is_array(reset($dataSet))) {
            return 0;
        }

        $options = $query->parseOptions();

        if (!empty($options['limit']) && is_numeric($options['limit'])) {
            $limit = (int) $options['limit'];
        } else {
            $limit = 0;
        }

        if (0 === $limit && count($dataSet) >= 5000) {
            $limit = 1000;
        }

        if ($limit) {
            // 分批写入 自动启动事务支持
            $this->begin();

            try {
                $array = array_chunk($dataSet, $limit, true);
                $count = 0;

                foreach ($array as $item) {
                    $sql = $this->builder->insertAll($query, $item);
                    $count += $this->pdoExecute($query, $sql);
                }

                // 提交事务
                $this->commit();
            } catch (\Exception  | \Throwable $e) {
                $this->rollback();

                throw $e;
            }

            return $count;
        }

        $sql = $this->builder->insertAll($query, $dataSet);

        return $this->pdoExecute($query, $sql);
    }

    /**
     * 删除记录.
     * @param BaseQuery $query 查询对象
     * @throws PDOException
     * @return int
     */
    public function del(BaseQuery $query): int
    {
        // 分析查询表达式
        $query->parseOptions();

        // 生成删除SQL语句
        $sql = $this->builder->delete($query);

        // 执行操作
        $result = $this->pdoExecute($query, $sql);

        if ($result) {
//            $this->db->trigger('after_delete', $query);
        }

        return $result;
    }

    /**
     * 得到某个字段的值
     * @param BaseQuery $query 查询对象
     * @param string $aggregate 聚合方法
     * @param string|Raw $field 字段名
     * @param bool $force 强制转为数字类型
     * @return mixed
     * @throws DbException
     * @throws Throwable
     */
    public function aggregate(BaseQuery $query, string $aggregate, string | Raw $field, bool $force = false)
    {
        if (is_string($field) && 0 === stripos($field, 'DISTINCT ')) {
            [$distinct, $field] = explode(' ', $field);
        }

        $field = $aggregate . '(' . (!empty($distinct) ? 'DISTINCT ' : '') . $this->builder->parseKey($query, $field, true) . ') AS duomai_' . strtolower($aggregate);

        $result = $this->value($query, $field, 0, false);

        return $force ? (float) $result : $result;
    }
}