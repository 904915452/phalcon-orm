<?php

declare(strict_types=1);

namespace Dm\PhalconOrm\exception;

/**
 * PDO异常处理类
 * 重新封装了系统的\PDOException类.
 */
class PDOException extends DbException
{
    /**
     * PDOException constructor.
     *
     * @param \PDOException $exception
     * @param array         $config
     * @param string        $sql
     * @param int           $code
     */
    public function __construct(\PDOException $exception, array $config = [], string $sql = '', int $code = 10501)
    {
        $error = $exception->errorInfo;
        $message = $exception->getMessage();

        if (!empty($error)) {
            $this->setData('PDO Error Info', [
                'SQLSTATE'             => $error[0],
                'Driver Error Code'    => $error[1] ?? 0,
                'Driver Error Message' => $error[2] ?? '',
            ]);
        }

        parent::__construct($message, $config, $sql, $code);
    }
}
