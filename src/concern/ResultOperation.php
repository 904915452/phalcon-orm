<?php
declare(strict_types=1);

namespace Dm\PhalconOrm\concern;

use Dm\PhalconOrm\exception\DataNotFoundException;
use Dm\PhalconOrm\exception\DbException;
use Dm\PhalconOrm\exception\ModelNotFoundException;
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
}
