<?php
namespace Dm\PhalconOrm\exception;

class DbException extends \Exception
{
    /**
     * 保存异常页面显示的额外Debug数据.
     *
     * @var array
     */
    protected $data = [];

    /**
     * 设置异常额外的Debug数据
     * 数据将会显示为下面的格式.
     *
     * Exception Data
     * --------------------------------------------------
     * Label 1
     *   key1      value1
     *   key2      value2
     * Label 2
     *   key1      value1
     *   key2      value2
     *
     * @param string $label 数据分类，用于异常页面显示
     * @param array  $data  需要显示的数据，必须为关联数组
     */
    final protected function setData(string $label, array $data)
    {
        $this->data[$label] = $data;
    }

    /**
     * 获取异常额外Debug数据
     * 主要用于输出到异常页面便于调试.
     *
     * @return array 由setData设置的Debug数据
     */
    final public function getData()
    {
        return $this->data;
    }

    /**
     * DbException constructor.
     *
     * @param string $message
     * @param array  $config
     * @param string $sql
     * @param int    $code
     */
    public function __construct(string $message, array $config = [], string $sql = '', int $code = 10500)
    {
        $this->message = $message;
        $this->code = $code;

        $this->setData('Database Status', [
            'Error Code'    => $code,
            'Error Message' => $message,
            'Error SQL'     => $sql,
        ]);

        unset($config['username'], $config['password']);
        $this->setData('Database Config', $config);
    }
}