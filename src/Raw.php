<?php

namespace Dm\PhalconOrm;

use Stringable;

/**
 * SQL Raw.
 */
class Raw
{
    /**
     * 创建一个查询表达式.
     *
     * @param string|Stringable $value
     * @param array  $bind
     *
     * @return void
     */
    public function __construct(protected string|Stringable $value, protected array $bind = [])
    {
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
