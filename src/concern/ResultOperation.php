<?php
declare(strict_types=1);

namespace Dm\PhalconOrm\concern;

use Dm\PhalconOrm\exception\DataNotFoundException;
use Dm\PhalconOrm\exception\DbException;
use Dm\PhalconOrm\exception\ModelNotFoundException;
use Dm\PhalconOrm\helper\Collection;
use Dm\PhalconOrm\helper\Str;
use Dm\PhalconOrm\model\Model;

/**
 * 查询数据处理.
 */
trait ResultOperation
{
    /**
     * 查询失败 抛出异常.
     * @return void
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     */
    protected function throwNotFound(): void
    {
        if (!empty($this->model)) {
            $class = get_class($this->model);

            throw new ModelNotFoundException('model data Not Found:' . $class, $class, $this->options);
        }

        $table = $this->getTable();

        throw new DataNotFoundException('table data not Found:' . $table, $table, $this->options);
    }

    /**
     * 处理数据.
     *
     * @param array $result 查询数据
     *
     * @return void
     */
    protected function result(array &$result): void
    {
        // JSON数据处理
        if (!empty($this->options['json'])) {
//            $this->jsonResult($result);
        }

        // 查询数据处理
        foreach ($this->options['filter'] as $filter) {
            $result = call_user_func_array($filter, [$result, $this->options]);
        }

        // 获取器
        if (!empty($this->options['with_attr'])) {
            $this->getResultAttr($result, $this->options['with_attr']);
        }
    }

    /**
     * 使用获取器处理数据.
     * @param array $result   查询数据
     * @param array $withAttr 字段获取器
     * @return void
     */
    protected function getResultAttr(array &$result, array $withAttr = []): void
    {
        foreach ($withAttr as $name => $closure) {
            $name = Str::snake($name);

            if (str_contains($name, '.')) {
                // 支持JSON字段 获取器定义
                [$key, $field] = explode('.', $name);

                if (isset($result[$key])) {
                    $result[$key][$field] = $closure($result[$key][$field] ?? null, $result[$key]);
                }
            } else {
                $result[$name] = $closure($result[$name] ?? null, $result);
            }
        }
    }

    /**
     * 处理空数据.
     *
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws DataNotFoundException
     *
     * @return array|Model|null|static
     */
    protected function resultToEmpty()
    {
        if (!empty($this->options['fail'])) {
            $this->throwNotFound();
        } elseif (!empty($this->options['allow_empty'])) {
            return !empty($this->model) ? $this->model->newInstance() : [];
        }
    }

    /**
     * 处理数据集.
     * @param array $resultSet    数据集
     * @param bool  $toCollection 是否转为对象
     * @return void
     */
    protected function resultSet(array &$resultSet, bool $toCollection = true): void
    {
        foreach ($resultSet as &$result) {
            $this->result($result);
        }
        // 返回Collection对象
        if ($toCollection) {
            $resultSet = new Collection($resultSet);
        }
    }
}
