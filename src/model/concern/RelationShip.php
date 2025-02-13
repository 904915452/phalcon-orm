<?php
namespace Dm\PhalconOrm\model\concern;

use Dm\PhalconOrm\helper\Str;

trait RelationShip
{
    /**
     * 模型关联数据.
     *
     * @var array
     */
    private $relation = [];

    /**
     * 关联自动写入信息.
     *
     * @var array
     */
    protected $relationWrite = [];

    /**
     * 检查属性是否为关联属性 如果是则返回关联方法名.
     *
     * @param string $attr 关联属性名
     *
     * @return string|false
     */
    protected function isRelationAttr(string $attr)
    {
        $relation = Str::camel($attr);

        if ((method_exists($this, $relation) && !method_exists('Dm\PhalconOrm\model\Model', $relation)) || isset(static::$macro[static::class][$relation])) {
            return $relation;
        }

        return false;
    }
}