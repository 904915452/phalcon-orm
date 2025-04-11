<?php

namespace Dm\PhalconOrm;

/**
 * SQL Raw.
 */
class Raw
{
    protected $value;
    protected $bind = [];

    /**
     * 创建一个查询表达式.
     *
     * @param string $value
     * @param array $bind
     *
     * @return void
     */
    public function __construct($value, array $bind = [])
    {
        $this->bind = $bind;
        $this->value = $value;
    }

    /**
     * 获取表达式.
     *
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * 获取参数绑定.
     *
     * @return array
     */
    public function getBind(): array
    {
        return $this->bind;
    }
}
