<?php
declare(strict_types=1);

namespace Dm\PhalconOrm\concern;


use Dm\PhalconOrm\model\Model;

/**
 * 模型及关联查询.
 */
trait ModelRelationQuery
{
    /**
     * 当前模型对象
     * @var Model
     */
    protected $model;

    /**
     * 指定模型.
     *
     * @param Model $model 模型对象实例
     *
     * @return $this
     */
    public function model(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * 获取当前的模型对象
     *
     * @return Model|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * 查询数据转换为模型对象
     * @param array $result 查询数据
     * @return void
     */
    protected function resultToModel(array &$result): void
    {
        // JSON数据处理
        if (!empty($this->options['json'])) {
            $this->jsonModelResult($result);
        }

        // 实时读取延迟数据
        if (!empty($this->options['lazy_fields'])) {
            $id = $this->getKey($result);
            foreach ($this->options['lazy_fields'] as $field) {
                if (isset($result[$field])) {
                    $result[$field] += $this->getLazyFieldValue($field, $id);
                }
            }
        }

        $result = $this->model->newInstance(
            $result,
            !empty($this->options['is_resultSet']) ? null : $this->getModelUpdateCondition($this->options),
            $this->options
        );

        // 模型数据处理
        foreach ($this->options['filter'] as $filter) {
            call_user_func_array($filter, [$result, $this->options]);
        }

        // 关联查询
        if (!empty($this->options['relation'])) {
            $result->relationQuery($this->options['relation'], $this->options['with_relation_attr']);
        }

        // 关联预载入查询
        if (empty($this->options['is_resultSet'])) {
            foreach (['with', 'with_join'] as $with) {
                if (!empty($this->options[$with])) {
                    $result->eagerlyResult(
                        $this->options[$with],
                        $this->options['with_relation_attr'],
                        'with_join' == $with,
                        $this->options['with_cache'] ?? false
                    );
                }
            }
        }

        // 关联统计查询
        if (!empty($this->options['with_aggregate'])) {
            foreach ($this->options['with_aggregate'] as $val) {
                $result->relationCount($this, $val[0], $val[1], $val[2], false);
            }
        }

        // 动态获取器
        if (!empty($this->options['with_attr'])) {
            $result->withAttr($this->options['with_attr']);
        }

        foreach (['hidden', 'visible', 'append'] as $name) {
            if (!empty($this->options[$name])) {
                $result->$name($this->options[$name]);
            }
        }

        // 刷新原始数据
        $result->refreshOrigin();
    }

    /**
     * JSON字段数据转换.
     *
     * @param array $result 查询数据
     *
     * @return void
     */
    protected function jsonModelResult(array &$result): void
    {
        $withAttr = $this->options['with_attr'];
        foreach ($this->options['json'] as $name) {
            if (!isset($result[$name])) {
                continue;
            }

            $jsonData = json_decode($result[$name], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }

            if (isset($withAttr[$name])) {
                foreach ($withAttr[$name] as $key => $closure) {
                    $jsonData[$key] = $closure($jsonData[$key] ?? null, $jsonData);
                }
            }

            $result[$name] = !$this->options['json_assoc'] ? (object) $jsonData : $jsonData;
        }
    }
}
