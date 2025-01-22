<?php
namespace Dm\PhalconOrm\concern;
trait TableFieldInfo{
    /**
     * 获取字段类型信息.
     *
     * @return array
     */
    public function getFieldsBindType(): array
    {
        $fieldType = $this->getFieldsType();

        return array_map([$this->connection, 'getFieldBindType'], $fieldType);
    }

    /**
     * 获取字段类型信息.
     * @return array
     */
    public function getFieldsType(): array
    {
        if (!empty($this->options['field_type'])) {
            return $this->options['field_type'];
        }
        return $this->connection->getFieldsType($this->getTable());
    }
}