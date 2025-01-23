<?php
namespace Dm\PhalconOrm\connector;

use Dm\PhalconOrm\BaseQuery;
use Dm\PhalconOrm\builder\Mysql as MysqlBuilder;
use Dm\PhalconOrm\exception\DbEventException;
use Dm\PhalconOrm\exception\DbException;
use Dm\PhalconOrm\Query;
use PDO;
use Throwable;

class Mysql extends \Phalcon\Db\Adapter\Pdo\Mysql implements ConnectionInterface
{
    use ConnectionTrait;
    use PDOConnectionTrait;

    const PARAM_INT   = 1;
    const PARAM_STR   = 2;
    const PARAM_BOOL  = 5;
    const PARAM_FLOAT = 21;

    protected $db;

    /**
     * sql生成器
     * @var MysqlBuilder
     */
    protected MysqlBuilder $builder;

    /**
     * @var string
     */
    protected string $queryStr;

    /**
     * 参数绑定
     * @var array
     */
    protected array $bind;



    /**
     * Constructor for Phalcon\Db\Adapter\Pdo
     * @param $config
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $this->builder = new MysqlBuilder();
    }

    public function getQueryClass(): string
    {
        return Query::class;
    }

    /**
     * 获取配置信息
     * @return mixed|null
     */
    protected function getConfig(string $config = '')
    {
        if ('' === $config) {
            return $this->descriptor;
        }

        return $this->descriptor[$config] ?? null;
    }

    /**
     * 查找单条记录.
     * @param BaseQuery $query 查询对象
     * @return array
     * @throws Throwable
     * @throws DbException
     */
    public function first(BaseQuery $query): array
    {
        // 事件回调
        try {
            // $this->db->trigger('before_find', $query);
        } catch (DbEventException $e) {
            return [];
        }

        // 执行查询
        $resultSet = $this->pdoQuery($query, function ($query) {
            return $this->builder->select($query, true);
        });

        return $resultSet[0] ?? [];
    }

    /**
     * 查找记录.
     * @param BaseQuery $query 查询对象
     * @return array
     * @throws \Exception|Throwable
     */
    public function select(BaseQuery $query): array
    {
        try {
//            $this->db->trigger('before_select', $query);
        } catch (DbEventException $e) {
            return [];
        }

        // 执行查询操作
        return $this->pdoQuery($query, function ($query) {
            return $this->builder->select($query);
        });
    }

    /**
     * 取得数据表的字段信息.
     * @param string $tableName
     * @return array
     * @throws DbException
     * @throws Throwable
     */
    public function getFields(string $tableName): array
    {
        [$tableName] = explode(' ', $tableName);

        /*
         * 8.0+
         */
        if (!str_contains($tableName, '`')) {
            if (str_contains($tableName, '.')) {
                $tableName = str_replace('.', '`.`', $tableName);
            }
            $tableName = '`' . $tableName . '`';
        }

        $sql    = 'SHOW FULL COLUMNS FROM ' . $tableName;
        $pdo    = $this->getPDOStatement($sql);
        $result = $pdo->fetchAll(PDO::FETCH_ASSOC);
        $info   = [];

        if (!empty($result)) {
            foreach ($result as $key => $val) {
                $val = array_change_key_case($val);

                $info[$val['field']] = [
                    'name'    => $val['field'],
                    'type'    => $val['type'],
                    'notnull' => 'NO' == $val['null'],
                    'default' => $val['default'],
                    'primary' => strtolower($val['key']) == 'pri',
                    'autoinc' => strtolower($val['extra']) == 'auto_increment',
                    'comment' => $val['comment'],
                ];
            }
        }

        return $this->fieldCase($info);
    }
}