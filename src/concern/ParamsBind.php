<?php
declare(strict_types=1);

namespace Dm\PhalconOrm\concern;

/**
 * 参数绑定支持
 */
trait ParamsBind
{
    /**
     * 当前参数绑定.
     *
     * @var array
     */
    protected $bind = [];

    /**
     * 批量参数绑定.
     *
     * @param array $value 绑定变量值
     *
     * @return $this
     */
    public function bind(array $value)
    {
        $this->bind = array_merge($this->bind, $value);

        return $this;
    }

    /**
     * 单个参数绑定.
     * @param mixed $value 绑定变量值
     * @param int|null $type 绑定类型
     * @param string|null $name 绑定标识
     * @return string
     */
    public function bindValue($value, int $type = null, string $name = null)
    {
        $name = $name ?: 'Bind_' . (count($this->bind) + 1) . '_' . mt_rand() . '_';

//        $this->bind[$name] = [$value, $type ?: Connection::PARAM_STR];
        $this->bind[$name] = [$value, $type ?: 2];

        return $name;
    }

    /**
     * 检测参数是否已经绑定.
     *
     * @param string $key 参数名
     *
     * @return bool
     */
    public function isBind(string $key)
    {
        return isset($this->bind[$key]);
    }

    /**
     * 设置自动参数绑定.
     * @param bool $bind 是否自动参数绑定
     * @return $this
     */
    public function autoBind(bool $bind)
    {
        $this->options['auto_bind'] = $bind;
        return $this;
    }

    /**
     * 检测是否开启自动参数绑定.
     * @return bool
     */
    public function isAutoBind(): bool
    {
        /*
        $autoBind = null;
        if (null !== $this->getOptions('auto_bind')) {
            $autoBind = $this->getOptions('auto_bind');
        }*/

        return (bool) $this->getOptions('auto_bind');
    }

    /**
     * 参数绑定.
     * @param string $sql  绑定的sql表达式
     * @param array  $bind 参数绑定
     * @return void
     */
    public function bindParams(string &$sql, array $bind = []): void
    {
        foreach ($bind as $key => $value) {
            if (is_array($value)) {
                $name = $this->bindValue($value[0], $value[1], $value[2] ?? null);
            } else {
                $name = $this->bindValue($value);
            }

            if (is_numeric($key)) {
                $sql = substr_replace($sql, ':' . $name, strpos($sql, '?'), 1);
            } else {
                $sql = str_replace(':' . $key, ':' . $name, $sql);
            }
        }
    }

    /**
     * 获取绑定的参数 并清空.
     *
     * @param bool $clear 是否清空绑定数据
     *
     * @return array
     */
    public function getBind(bool $clear = true): array
    {
        $bind = $this->bind;
        if ($clear) {
            $this->bind = [];
        }

        return $bind;
    }
}
