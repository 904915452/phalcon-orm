<?php
namespace Dm\PhalconOrm\concern;
trait TableFieldInfo{

    /**
     * 获取数据表字段信息.
     * @param string $tableName 数据表名
     * @return array
     */
    public function getTableFields(string $tableName = ''): array
    {
        if ('' == $tableName) {
            $tableName = $this->getTable();
        }

        return $this->connection->getTableFields($tableName);
    }

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