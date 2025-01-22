<?php
declare(strict_types=1);

namespace Dm\PhalconOrm\exception;

/**
 * PDO参数绑定异常.
 */
class BindParamException extends DbException
{
    /**
     * BindParamException constructor.
     *
     * @param string $message
     * @param array  $config
     * @param string $sql
     * @param array  $bind
     * @param int    $code
     */
    public function __construct(string $message, array $config, string $sql, array $bind, int $code = 10502)
    {
        $this->setData('Bind Param', $bind);
        parent::__construct($message, $config, $sql, $code);
    }
}
