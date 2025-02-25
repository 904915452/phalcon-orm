<?php
declare (strict_types = 1);

namespace Dm\PhalconOrm\model\concern;

use Dm\PhalconOrm\helper\Collection;
use Dm\PhalconOrm\exception\DbException as Exception;
use Dm\PhalconOrm\helper\Str;
use Dm\PhalconOrm\model\Model;
use Dm\PhalconOrm\helper\Collection as ModelCollection;

/**
 * 模型数据转换处理.
 */
trait Conversion
{
    /**
     * 数据输出显示的属性.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * 数据输出隐藏的属性.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 数据输出需要追加的属性.
     *
     * @var array
     */
    protected $append = [];

    /**
     * 场景.
     *
     * @var array
     */
    protected $scene = [];

    /**
     * 数据输出字段映射.
     *
     * @var array
     */
    protected $mapping = [];

    /**
     * 数据集对象名.
     *
     * @var string
     */
    protected $resultSetType;

    /**
     * 数据命名是否自动转为驼峰.
     *
     * @var bool
     */
    protected $convertNameToCamel;

    /**
     * 转换数据为驼峰命名（用于输出）.
     *
     * @param bool $toCamel 是否自动驼峰命名
     *
     * @return $this
     */
    public function convertNameToCamel(bool $toCamel = true)
    {
        $this->convertNameToCamel = $toCamel;

        return $this;
    }

    /**
     * 设置输出层场景.
     *
     * @param string $scene 场景名称
     *
     * @return $this
     */
    public function scene(string $scene)
    {
        if (isset($this->scene[$scene])) {
            $data = $this->scene[$scene];
            foreach (['append', 'hidden', 'visible'] as $name) {
                if (isset($data[$name])) {
                    $this->$name($data[$name]);
                }
            }
        }

        return $this;
    }

    /**
     * 设置附加关联对象的属性.
     *
     * @param string       $attr   关联属性
     * @param string|array $append 追加属性名
     *
     * @throws Exception
     *
     * @return $this
     */
    public function appendRelationAttr(string $attr, array $append)
    {
        $relation = Str::camel($attr);

        $model = $this->relation[$relation] ?? $this->getRelationData($this->$relation());

        if ($model instanceof Model) {
            foreach ($append as $key => $attr) {
                $key = is_numeric($key) ? $attr : $key;
                if (isset($this->data[$key])) {
                    throw new Exception('bind attr has exists:' . $key);
                }

                $this->data[$key] = $model->getAttr($attr);
            }
        }

        return $this;
    }

    /**
     * 设置需要附加的输出属性.
     *
     * @param array $append 属性列表
     * @param bool  $merge  是否合并
     *
     * @return $this
     */
    public function append(array $append = [], bool $merge = false)
    {
        $this->append = $merge ? array_merge($this->append, $append) : $append;

        return $this;
    }

    /**
     * 设置需要隐藏的输出属性.
     *
     * @param array $hidden 属性列表
     * @param bool  $merge  是否合并
     *
     * @return $this
     */
    public function hidden(array $hidden = [], bool $merge = false)
    {
        $this->hidden = $merge ? array_merge($this->hidden, $hidden) : $hidden;

        return $this;
    }

    /**
     * 设置需要输出的属性.
     *
     * @param array $visible
     * @param bool  $merge   是否合并
     *
     * @return $this
     */
    public function visible(array $visible = [], bool $merge = false)
    {
        $this->visible = $merge ? array_merge($this->visible, $visible) : $visible;

        return $this;
    }

    /**
     * 设置属性的映射输出.
     *
     * @param array $map
     *
     * @return $this
     */
    public function mapping(array $map)
    {
        $this->mapping = $map;

        return $this;
    }

    /**
     * 转换当前模型对象为数组.
     * @return array
     */
    public function toArr(): array
    {
        $item = $visible = $hidden = [];

        $hasVisible = false;

        foreach ($this->visible as $key => $val) {
            if (is_string($val)) {
                if (str_contains($val, '.')) {
                    [$relation, $name]    = explode('.', $val);
                    $visible[$relation][] = $name;
                } else {
                    $visible[$val] = true;
                    $hasVisible    = true;
                }
            } else {
                $visible[$key] = $val;
            }
        }

        foreach ($this->hidden as $key => $val) {
            if (is_string($val)) {
                if (str_contains($val, '.')) {
                    [$relation, $name]   = explode('.', $val);
                    $hidden[$relation][] = $name;
                } else {
                    $hidden[$val] = true;
                }
            } else {
                $hidden[$key] = $val;
            }
        }

        // 追加属性（必须定义获取器）
        foreach ($this->append as $key => $name) {
            $this->appendAttrToArray($item, $key, $name, $visible, $hidden);
        }

        // 合并关联数据
        $data = array_merge($this->data, $this->relation);

        foreach ($data as $key => $val) {
            if ($val instanceof Model || $val instanceof ModelCollection) {
                // 关联模型对象
                if (isset($visible[$key]) && is_array($visible[$key])) {
                    $val->visible($visible[$key]);
                } elseif (isset($hidden[$key]) && is_array($hidden[$key])) {
                    $val->hidden($hidden[$key], true);
                }
                // 关联模型对象
                if (!array_key_exists($key, $this->relation) || (array_key_exists($key, $this->with) && (!isset($hidden[$key]) || true !== $hidden[$key]))) {
                    $item[$key] = $val->toArr();
                }
            } elseif (isset($visible[$key])) {
                $item[$key] = $this->getAttr($key);
            } elseif (!isset($hidden[$key]) && !$hasVisible) {
                $item[$key] = $this->getAttr($key);
            }

            if (isset($this->mapping[$key])) {
                // 检查字段映射
                $mapName        = $this->mapping[$key];
                $item[$mapName] = $item[$key];
                unset($item[$key]);
            }
        }

        if ($this->convertNameToCamel) {
            foreach ($item as $key => $val) {
                $name = Str::camel($key);
                if ($name !== $key) {
                    $item[$name] = $val;
                    unset($item[$key]);
                }
            }
        }

        return $item;
    }

    protected function appendAttrToArray(array &$item, $key, array | string $name, array $visible, array $hidden): void
    {
        if (is_array($name)) {
            // 批量追加关联对象属性
            $relation   = $this->getRelationWith($key, $hidden, $visible);
            $item[$key] = $relation ? $relation->append($name)->toArray() : [];
        } elseif (str_contains($name, '.')) {
            // 追加单个关联对象属性
            [$key, $attr] = explode('.', $name);
            $relation     = $this->getRelationWith($key, $hidden, $visible);
            $item[$key]   = $relation ? $relation->append([$attr])->toArray() : [];
        } else {
            $value       = $this->getAttr($name);
            $item[$name] = $value;

            $this->getBindAttrValue($name, $value, $item);
        }
    }

    protected function getRelationWith(string $key, array $hidden, array $visible)
    {
        return null;
        /*$relation = $this->getRelation($key, true);
        if ($relation) {
            if (isset($visible[$key])) {
                $relation->visible($visible[$key]);
            } elseif (isset($hidden[$key])) {
                $relation->hidden($hidden[$key]);
            }
        }
        return $relation;*/
    }

    protected function getBindAttrValue(string $name, $value, array &$item = [])
    {
        return false;
//        $relation = $this->isRelationAttr($name);
//        if (!$relation) {
//            return false;
//        }
//        $modelRelation = $this->$relation();
//
//        if ($modelRelation instanceof OneToOne) {
//            $bindAttr = $modelRelation->getBindAttr();
//
//            if (!empty($bindAttr)) {
//                unset($item[$name]);
//            }
//
//            foreach ($bindAttr as $key => $attr) {
//                $key = is_numeric($key) ? $attr : $key;
//
//                if (isset($item[$key])) {
//                    throw new Exception('bind attr has exists:' . $key);
//                }
//
//                $item[$key] = $value ? $value->getAttr($attr) : null;
//            }
//        }
    }

    /**
     * 转换当前模型对象为JSON字符串.
     *
     * @param int $options json参数
     *
     * @return string
     */
    public function toJson(int $options = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArr(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    // JsonSerializable
    public function jsonSerialize(): array
    {
        return $this->toArr();
    }

    /**
     * 转换数据集为数据集对象
     * @param array|Collection $collection 数据集
     * @param string|null $resultSetType 数据集类
     * @return Collection
     */
    public function toCollection(iterable $collection = [], string $resultSetType = null): Collection
    {
        $resultSetType = $resultSetType ?: $this->resultSetType;

        if ($resultSetType && str_contains($resultSetType, '\\')) {
            $collection = new $resultSetType($collection);
        } else {
            $collection = new ModelCollection($collection);
        }

        return $collection;
    }
}
