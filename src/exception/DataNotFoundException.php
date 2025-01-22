<?php

declare(strict_types=1);

namespace Dm\PhalconOrm\exception;

use Dm\PhalconOrm\exception\DbException;

class DataNotFoundException extends DbException
{
    protected $table;

    /**
     * DbException constructor.
     *
     * @param string $message
     * @param string $table
     * @param array  $config
     */
    public function __construct(string $message, string $table = '', array $config = [])
    {
        $this->message = $message;
        $this->table = $table;

        $this->setData('Database Config', $config);
    }

    /**
     * 获取数据表名.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
