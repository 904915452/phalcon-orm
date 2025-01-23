<?php

namespace Dm\PhalconOrm;

use Closure;
use Dm\PhalconOrm\concern\ModelRelationQuery;
use Dm\PhalconOrm\concern\ResultOperation;
use Dm\PhalconOrm\concern\WhereQuery;
use Dm\PhalconOrm\exception\DataNotFoundException;
use Dm\PhalconOrm\exception\ModelNotFoundException;
use Exception;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;

/**
 * 数据查询基础类.
 * @method getBind()
 */
abstract class BaseQuery
{

    use ModelRelationQuery;
    use WhereQuery;
    use ResultOperation;

    /**
     * 数据库连接类
     * @var
     */
    protected $connection;

    /**
     * @var string 数据表名称
     */
    protected string $name;

    /**
     * 主键
     * @var string|array|bool
     */
    protected string|array|bool $pk = 'id';

    /**
     * 当前查询参数.
     * @var array
     */
    protected array $options = [];
    protected string $prefix = '';

    public function __construct($connector)
    {
        $this->connection = $connector;
    }

    public function getConnection(): AbstractPdo
    {
        return $this->connection;
    }

    /**
     * 指定当前数据表名（不含前缀）.
     * @param string $name 不含前缀的数据表名字
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 指定数据表主键.
     * @param string|array|bool $pk 主键
     * @return $this
     */
    public function pk(string|array|bool $pk)
    {
        $this->pk = $pk;
        return $this;
    }

    /**
     * 获取主键
     * @return array|bool|string
     */
    public function getPk()
    {
        return $this->pk;
    }

    /**
     * 获取当前的数据表名称.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 指定当前操作的数据表.
     * @param string|array|Raw $table 表名
     * @return $this
     */
    public function table(string|array|Raw $table)
    {
        if (is_string($table) && !str_contains($table, ')')) {
            $table = $this->tableStr($table);
        } elseif (is_array($table)) {
            $table = $this->tableArr($table);
        }

        $this->options['table'] = $table;
        return $this;
    }

    /**
     * 指定数据表（字符串）.
     * @param string $table 表名
     * @return array|string
     */
    protected function tableStr(string $table): array|string
    {
        if (!str_contains($table, ',')) {
            // 单表
            if (str_contains($table, ' ')) {
                [$item, $alias] = explode(' ', $table);
                $table = [];
                $this->alias([$item => $alias]);
                $table[$item] = $alias;
            }
        } else {
            // 多表
            $tables = explode(',', $table);
            $table = [];

            foreach ($tables as $item) {
                $item = trim($item);
                if (str_contains($item, ' ')) {
                    [$item, $alias] = explode(' ', $item);
                    $this->alias([$item => $alias]);
                    $table[$item] = $alias;
                } else {
                    $table[] = $item;
                }
            }
        }
        return $table;
    }

    /**
     * 指定数据表别名.
     * @param array|string $alias 数据表别名
     * @return $this
     */
    public function alias(array|string $alias)
    {
        if (is_array($alias)) {
            $this->options['alias'] = $alias;
        } else {
            $table = $this->getTable();

            $this->options['alias'][$table] = $alias;
        }

        return $this;
    }

    /**
     * 指定多个数据表（数组格式）.
     * @param array $tables 表名列表
     * @return array
     */
    protected function tableArr(array $tables): array
    {
        $table = [];
        foreach ($tables as $key => $val) {
            if (is_numeric($key)) {
                $table[] = $val;
            } else {
                $this->alias([$key => $val]);
                $table[$key] = $val;
            }
        }

        return $table;
    }

    /**
     * 得到当前或者指定名称的数据表.
     * @param string $name 不含前缀的数据表名字
     * @return string|array|Raw
     */
    public function getTable(string $name = '')
    {
        if (empty($name) && isset($this->options['table'])) {
            return $this->options['table'];
        }

        $name = $name ?: $this->name;
        return $this->prefix . $name;
    }

    /**
     * 查询参数批量赋值
     * @param array $options 表达式参数
     * @return $this
     */
    protected function options(array $options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * 获取当前的查询参数.
     * @param string $name 参数名
     * @return mixed
     */
    public function getOptions(string $name = '')
    {
        if ('' === $name) {
            return $this->options;
        }
        return $this->options[$name] ?? null;
    }

    /**
     * 设置当前的查询参数.
     * @param string $option 参数名
     * @param mixed $value 参数值
     * @return $this
     */
    public function setOption(string $option, $value)
    {
        $this->options[$option] = $value;

        return $this;
    }

    /**
     * 去除查询参数.
     * @param string $option 参数名 留空去除所有参数
     * @return $this
     */
    public function removeOption(string $option = '')
    {
        if ('' === $option) {
            $this->options = [];
            $this->bind = [];
        } elseif (isset($this->options[$option])) {
            unset($this->options[$option]);
        }

        return $this;
    }

    /**
     * 查找单条记录.
     * @param mixed $data 主键数据
     * @return mixed
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     * @throws Exception
     */
    public function first($data = null)
    {
        if (!is_null($data)) {
            // AR模式分析主键条件
            $this->parsePkWhere($data);
        }

        if (empty($this->options['where']) && empty($this->options['scope']) && empty($this->options['order']) && empty($this->options['sort'])) {
            $result = [];
        } else {
            $result = $this->connection->first($this);
        }

        // 数据处理
//        if (empty($result)) {
//            return $this->resultToEmpty();
//        }
//
//        if (!empty($this->model)) {
//            // 返回模型对象
//            $this->resultToModel($result);
//        } else {
//            $this->result($result);
//        }

        return $result;
    }

    /**
     * 查找记录.
     *
     * @param array $data 主键数据
     * @throws Exception
     */
    public function select(array $data = [])
    {
        if (!empty($data)) {
            // 主键条件分析
            $this->parsePkWhere($data);
        }

        $resultSet = $this->connection->select($this);

        // 返回结果处理
//        if (!empty($this->options['fail']) && count($resultSet) == 0) {
//            $this->throwNotFound();
//        }
//
//        // 数据列表读取后的处理
//        if (!empty($this->model)) {
//            // 生成模型对象
//            $resultSet = $this->resultSetToModelCollection($resultSet);
//        } else {
//            $this->resultSet($resultSet);
//        }

        return $resultSet;
    }

    /**
     * 得到某个字段的值
     * @param string $field   字段名
     * @param mixed  $default 默认值
     * @return mixed
     */
    public function value(string $field, $default = null)
    {
        $result = $this->connection->value($this, $field, $default);

        $array[$field] = $result;
//        $this->result($array);

        return $array[$field];
    }

    /**
     * 把主键值转换为查询条件 支持复合主键.
     * @param mixed $data 主键数据
     * @return void
     * @throws Exception
     */
    public function parsePkWhere($data): void
    {
        $pk = $this->getPk();

        if (!is_string($pk)) {
            return;
        }

        // 获取数据表
        if (empty($this->options['table'])) {
            $this->options['table'] = $this->getTable();
        }

        $table = is_array($this->options['table']) ? key($this->options['table']) : $this->options['table'];

        if (!empty($this->options['alias'][$table])) {
            $alias = $this->options['alias'][$table];
        }

        $key = isset($alias) ? $alias . '.' . $pk : $pk;
        // 根据主键查询
        if (is_array($data)) {
            $this->where($key, 'in', $data);
        } else {
            $this->where($key, '=', $data);
            $this->options['key'] = $data;
        }
    }

    /**
     * 分析表达式（可用于查询或者写入操作）.
     *
     * @return array
     */
    public function parseOptions(): array
    {
        // 执行全局查询范围
//        $this->scopeQuery();

        $options = $this->getOptions();

        // 获取数据表
        if (empty($options['table'])) {
            $options['table'] = $this->getTable();
        }

        if (!isset($options['where'])) {
            $options['where'] = [];
        }

        /*
        elseif (isset($options['view'])) {
            // 视图查询条件处理
            // $this->parseView($options);
        }
        */

        foreach (['data', 'order', 'join', 'union', 'filter', 'json', 'with_attr', 'with_relation_attr'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = [];
            }
        }

        foreach (['master', 'lock', 'fetch_sql', 'array', 'distinct', 'procedure', 'with_cache'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = false;
            }
        }

        foreach (['group', 'having', 'limit', 'force', 'comment', 'partition', 'duplicate', 'extra'] as $name) {
            if (!isset($options[$name])) {
                $options[$name] = '';
            }
        }

        if (isset($options['page'])) {
            // 根据页数计算limit
            [$page, $listRows] = $options['page'];

            $page = $page > 0 ? $page : 1;
            $listRows = $listRows ?: (is_numeric($options['limit']) ? $options['limit'] : 20);
            $offset = $listRows * ($page - 1);

            $options['limit'] = $offset . ',' . $listRows;
        }

        $this->options = $options;

        return $options;
    }

    /**
     * 创建一个新的查询对象
     *
     * @return BaseQuery
     */
    public function newQuery(): BaseQuery
    {
        $query = new static($this->connection);

        if ($this->model) {
            $query->model($this->model);
        }

        if (isset($this->options['table'])) {
            $query->table($this->options['table']);
        } else {
            $query->name($this->name);
        }

        if (!empty($this->options['json'])) {
//            $query->json($this->options['json'], $this->options['json_assoc']);
        }

        if (isset($this->options['field_type'])) {
            $query->setFieldType($this->options['field_type']);
        }

        return $query;
    }

    /**
     * 指定查询字段.
     *
     * @param string|array|Raw|true $field 字段信息
     *
     * @return $this
     */
    public function field(string|array|Raw|bool $field)
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Raw) {
            $this->options['field'][] = $field;

            return $this;
        }

        if (is_string($field)) {
            if (preg_match('/[\<\'\"\(]/', $field)) {
                return $this->fieldRaw($field);
            }

            $field = array_map('trim', explode(',', $field));
        }

        if (true === $field) {
            // 获取全部字段
            $fields = $this->getTableFields();
            $field = $fields ?: ['*'];
        }

        if (isset($this->options['field'])) {
            $field = array_merge((array)$this->options['field'], $field);
        }

        $this->options['field'] = array_unique($field, SORT_REGULAR);

        return $this;
    }

    /**
     * 指定排序 order('id','desc') 或者 order(['id'=>'desc','create_time'=>'desc']).
     * @param string|array|Raw $field 排序字段
     * @param string $order 排序
     * @return $this
     */
    public function order(string|array|Raw $field, string $order = '')
    {
        if (empty($field)) {
            return $this;
        } elseif ($field instanceof Raw) {
            $this->options['order'][] = $field;

            return $this;
        }

        if (is_string($field)) {
            if (!empty($this->options['via'])) {
                $field = $this->options['via'] . '.' . $field;
            }
            if (str_contains($field, ',')) {
                $field = array_map('trim', explode(',', $field));
            } else {
                $field = empty($order) ? $field : [$field => $order];
            }
        } elseif (!empty($this->options['via'])) {
            foreach ($field as $key => $val) {
                if (is_numeric($key)) {
                    $field[$key] = $this->options['via'] . '.' . $val;
                } else {
                    $field[$this->options['via'] . '.' . $key] = $val;
                    unset($field[$key]);
                }
            }
        }

        if (!isset($this->options['order'])) {
            $this->options['order'] = [];
        }

        if (is_array($field)) {
            $this->options['order'] = array_merge($this->options['order'], $field);
        } else {
            $this->options['order'][] = $field;
        }

        return $this;
    }

    /**
     * 设置字段类型信息.
     * @param array $type 字段类型信息
     * @return $this
     */
    public function setFieldType(array $type)
    {
        $this->options['field_type'] = $type;
        return $this;
    }

    /**
     * 指定查询数量.
     * @param int $offset 起始位置
     * @param int|null $length 查询数量
     * @return $this
     */
    public function limit(int $offset, int $length = null)
    {
        $this->options['limit'] = $offset . ($length ? ',' . $length : '');
        return $this;
    }

    /**
     * 查询SQL组装 union.
     * @param string|array|Closure $union UNION
     * @param bool $all 是否适用UNION ALL
     * @return $this
     */
    public function union(string|array|Closure $union, bool $all = false)
    {
        $this->options['union']['type'] = $all ? 'UNION ALL' : 'UNION';
        if (is_array($union)) {
            $this->options['union'] = array_merge($this->options['union'], $union);
        } else {
            $this->options['union'][] = $union;
        }
        return $this;
    }

    /**
     * 查询SQL组装 union all.
     * @param mixed $union UNION数据
     * @return $this
     */
    public function unionAll(string|array|Closure $union)
    {
        return $this->union($union, true);
    }

    /**
     * 指定查询lock.
     * @param bool|string $lock 是否lock
     * @return $this
     */
    public function lock(bool|string $lock = false)
    {
        $this->options['lock'] = $lock;
        if ($lock) {
            $this->options['master'] = true;
        }
        return $this;
    }

    /**
     * 得到某个列的数组.
     * @param string|array $field 字段名 多个字段用逗号分隔
     * @param string       $key   索引
     * @return array
     */
    public function column(string | array $field, string $key = ''): array
    {
        $result = $this->connection->column($this, $field, $key);

//        if (count($result) != count($result, 1)) {
//            $this->resultSet($result, false);
//        }

        return $result;
    }
}