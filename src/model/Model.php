<?php

namespace Dm\PhalconOrm\model;

use Dm\PhalconOrm\DbManager;
use Dm\PhalconOrm\model\concern\Attribute;
use Dm\PhalconOrm\model\concern\Conversion;
use Dm\PhalconOrm\model\concern\RelationShip;
use Dm\PhalconOrm\model\concern\TimeStamp;
use Dm\PhalconOrm\Query;
use Phalcon\Mvc\Model as MvcModel;
use Phalcon\Mvc\ModelInterface;

/**
 * @method static select()
 * @method static first()
 * @method static order()
 * @method static limit()
 * @method static value()
 * @method static column()
 * @method static insert()
 * @method static hold()
 * @method static update()
 * @method delete()
 * @method static count()
 * @method static max()
 * @method static min()
 * @method static avg()
 * @method static sum()
 * @method static alias()
 * @method static field()
 * @method static group()
 * @method static having()
 * @method static join()
 * @method static union()
 * @method static unionAll()
 * @method static distinct()
 * @method static lock()
 * @method static fetchSql()
 * @method static where()
 * @method static whereOr()
 * @method static whereNull()
 * @method static whereNotNull()
 * @method static whereExists()
 * @method static whereNotExists()
 * @method static whereIn()
 * @method static whereNotIn()
 * @method static whereLike()
 * @method static whereNotLike()
 * @method static whereBetween()
 * @method static whereNotBetween()
 * @method static whereFindInSet()
 * @method static whereColumn()
 * @method static whereRaw()
 * @method static whereOrRaw()
 * @method static when()
 * @method static buildSql()
 * @method static paginate()
 * @method static withTrashed()
 * @method static onlyTrashed()
 */
abstract class Model extends MvcModel
{
    use Attribute;
    use TimeStamp;
    use Conversion;
    use RelationShip;

    /**
     * 数据库管理类
     * @var DbManager
     */
    protected static $db;

    /**
     * 主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 数据表后缀
     * @var string
     */
    protected $suffix;

    /**
     * 数据是否存在.
     * @var bool
     */
    private $exists = false;

    /**
     * 是否强制更新所有数据.
     *
     * @var bool
     */
    private $force = false;

    /**
     * 是否Replace.
     *
     * @var bool
     */
    private $replace = false;

    /**
     * 方法注入.
     * @var Closure[][]
     */
    protected static $macro = [];

    /**
     * 更新条件.
     * @var array
     */
    private $updateWhere;

    /**
     * 软删除字段默认值
     * @var mixed
     */
    protected $defaultSoftDelete;

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

        // 软删除
        if (property_exists($this, 'withTrashed') && !$this->withTrashed) {
            $this->withNoTrashed($query);
        }

        return $query;
    }

    /**
     * 获取当前的更新条件.
     * @return mixed
     */
    public function getWhere()
    {
        $pk = $this->getPk();

        if (is_string($pk) && isset($this->origin[$pk])) {
            $where = [[$pk, '=', $this->origin[$pk]]];
            $this->key = $this->origin[$pk];
        } elseif (is_array($pk)) {
            foreach ($pk as $field) {
                if (isset($this->origin[$field])) {
                    $where[] = [$field, '=', $this->origin[$field]];
                }
            }
        }

        if (empty($where)) {
            $where = empty($this->updateWhere) ? null : $this->updateWhere;
        }

        return $where;
    }

    /**
     * 保存当前数据对象
     * @param array|object $data 数据
     * @param string|null $sequence 自增序列名
     * @return bool
     */
    public function hold($data = [], string $sequence = null): bool
    {
        if ($data instanceof Model) {
            $data = $data->getData();
        } elseif (is_object($data)) {
            $data = get_object_vars($data);
        }

        // 数据对象赋值
        $this->setAttrs($data);

//        if ($this->isEmpty() || false === $this->trigger('BeforeWrite')) {
//            return false;
//        }

        $result = $this->isExists() ? $this->updateData() : $this->insertData($sequence);

        if (false === $result) {
            return false;
        }

        // 写入回调
//        $this->trigger('AfterWrite');

        if (!empty($this->change)) {
            // 处理递增递减数据
            foreach ($this->change as $field => $val) {
                $this->data[$field] = $val;
            }
            $this->change = [];
        }

        // 重新记录原始数据
        $this->origin = $this->data;
        $this->get = [];

        return true;
    }

    /**
     * 更新是否强制写入数据 而不做比较（亦可用于软删除的强制删除）.
     * @param bool $force
     * @return $this
     */
    public function force(bool $force = true)
    {
        $this->force = $force;
        return $this;
    }

    /**
     * 判断force.
     *
     * @return bool
     */
    public function isForce(): bool
    {
        return $this->force;
    }

    /**
     * 新增数据是否使用Replace.
     * @param bool $replace
     * @return $this
     */
    public function replace(bool $replace = true)
    {
        $this->replace = $replace;
        return $this;
    }

    /**
     * 设置数据是否存在.
     *
     * @param bool $exists
     *
     * @return $this
     */
    public function exists(bool $exists = true)
    {
        $this->exists = $exists;

        return $this;
    }

    /**
     * 判断数据是否存在数据库.
     *
     * @return bool
     */
    public function isExists(): bool
    {
        return $this->exists || $this->_exists($this->getModelsMetaData(), $this->getReadConnection());
    }

    /**
     * 判断模型是否为空.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    protected function checkData(): void
    {
    }

    protected function checkResult($result): void
    {
    }

    /**
     * 检查数据是否允许写入.
     *
     * @return array
     */
    protected function checkAllowFields(): array
    {
        // 检测字段
        if (empty($this->field)) {
            if (!empty($this->schema)) {
                $this->field = array_keys(array_merge($this->schema, $this->jsonType));
            } else {
                $query = $this->db();
                $table = $this->getSource();

                $this->field = $query->getConnection()->getTableFields($table);
            }

            return $this->field;
        }

        $field = $this->field;

        if ($this->autoWriteTimestamp) {
            array_push($field, $this->createTime, $this->updateTime);
        }

        if (!empty($this->disuse)) {
            // 废弃字段
            $field = array_diff($field, $this->disuse);
        }

        return $field;
    }

    /**
     * 保存写入数据.
     *
     * @return bool
     */
    protected function updateData(): bool
    {
        // 事件回调
//        if (false === $this->trigger('BeforeUpdate')) {
//            return false;
//        }


        $this->checkData();

        // 获取有更新的数据
        $data = $this->getChangedData();

        if (empty($data)) {
            // 关联更新
            /*
            if (!empty($this->relationWrite)) {
                $this->autoRelationUpdate();
            }*/

            return true;
        }

        if ($this->autoWriteTimestamp && $this->updateTime) {
            // 自动写入更新时间
            $data[$this->updateTime] = $this->autoWriteTimestamp();
            $this->data[$this->updateTime] = $data[$this->updateTime];
        }

        // 检查允许字段
        $allowFields = $this->checkAllowFields();

        foreach ($this->relationWrite as $name => $val) {
            if (!is_array($val)) {
                continue;
            }

            foreach ($val as $key) {
                if (isset($data[$key])) {
                    unset($data[$key]);
                }
            }
        }

        // 模型更新
        $db = $this->db(null);

        $db->transaction(function () use ($data, $allowFields, $db) {
            $this->key = null;
            $where = $this->getWhere();
            $result = $db->where($where)
                ->strict(false)
//                ->cache(true)
                ->setOption('key', $this->key)
                ->field($allowFields)
                ->update($data);

            $this->checkResult($result);

            // 关联更新
            if (!empty($this->relationWrite)) {
//                $this->autoRelationUpdate();
            }
        });

        // 更新回调
//        $this->trigger('AfterUpdate');

        return true;
    }

    /**
     * 新增写入数据.
     * @param string $sequence 自增名
     * @return bool
     */
    protected function insertData(string $sequence = null): bool
    {
//        if (false === $this->trigger('BeforeInsert')) {
//            return false;
//        }

        $this->checkData();
        $data = $this->data;

        // 时间戳自动写入
        if ($this->autoWriteTimestamp) {
            if ($this->createTime && !array_key_exists($this->createTime, $data)) {
                $data[$this->createTime] = $this->autoWriteTimestamp();
                $this->data[$this->createTime] = $data[$this->createTime];
            }

            if ($this->updateTime && !array_key_exists($this->updateTime, $data)) {
                $data[$this->updateTime] = $this->autoWriteTimestamp();
                $this->data[$this->updateTime] = $data[$this->updateTime];
            }
        }

        // 检查允许字段
        $allowFields = $this->checkAllowFields();

        $db = $this->db();

        $db->transaction(function () use ($data, $sequence, $allowFields, $db) {
            $result = $db->strict(false)
                ->field($allowFields)
                ->replace($this->replace)
                ->sequence($sequence)
                ->insert($data, true);

            // 获取自动增长主键
            if ($result) {
                $pk = $this->getPk();

                if (is_string($pk) && (!isset($this->data[$pk]) || '' == $this->data[$pk])) {
                    unset($this->get[$pk]);
                    $this->data[$pk] = $result;
                }
            }

            // 关联写入
            /*if (!empty($this->relationWrite)) {
                $this->autoRelationInsert();
            }*/
        });

        // 标记数据已经存在
        $this->exists = true;
        $this->origin = $this->data;

        // 新增回调
//        $this->trigger('AfterInsert');

        return true;
    }

    /**
     * 创建新的模型实例.
     * @param array $data 数据
     * @param mixed $where 更新条件
     * @param array $options 参数
     *
     * @return Model
     */
    public function newInstance(array $data = [], $where = null, array $options = []): Model
    {
        $model = new static($data);

//        if ($this->connection) {
//            $model->setConnector($this->connection);
//        }

//        if ($this->suffix) {
//            $model->setSuffix($this->suffix);
//        }

        if (empty($data)) {
            return $model;
        }

        $model->exists(true);

        $model->setUpdateWhere($where);

//        $model->trigger('AfterRead');

        return $model;
    }

    /**
     * 设置模型的更新条件.
     * @param mixed $where 更新条件
     * @return void
     */
    protected function setUpdateWhere($where): void
    {
        $this->updateWhere = $where;
    }

    /**
     * 修改器 设置数据对象的值
     * @param string $property 名称
     * @param mixed $value 值
     * @return void
     */
    public function __set($property, $value): void
    {
        $this->setAttr($property, $value);
    }

    /**
     * 获取器 获取数据对象的值
     * @param string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        $data = $this->getAttr($name);
        if (empty($data)) {
            return parent::__get($name);
        } else {
            return $data;
        }
    }

    /**
     * 检测数据对象的值
     * @param string $name 名称
     * @return bool
     */
    public function __isset($name): bool
    {
        return !is_null($this->getAttr($name));
    }

    /**
     * 销毁数据对象的值
     * @param string $name 名称
     * @return void
     */
    public function __unset(string $name): void
    {
        unset(
            $this->data[$name],
            $this->get[$name],
            $this->relation[$name]
        );
    }

    public function __call($method, $arguments)
    {
        $query = $this->db();
        if (in_array($method, get_class_methods($query))) {
            return call_user_func_array([$query, $method], $arguments);
        } else {
            return parent::__call($method, $arguments);
        }
    }

    public static function __callStatic($method, $arguments)
    {
        $model = new static();
        return call_user_func_array([$model->db(), $method], $arguments);
    }

    # 以下为重写phalcon基方法 ---------------------------------------------

    public function readAttribute($attribute)
    {
        if (!isset($this->$attribute)) {
            return null;
        }
        return $this->$attribute;
    }

    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        parent::assign($data, $whiteList, $dataColumnMap);

        foreach ($data as $key => $value) {
            $this->setAttr($key, $value);
        }

        return $this;
    }
}