<?php
namespace Dm\PhalconOrm\builder;

use Dm\PhalconOrm\Builder;
use Dm\PhalconOrm\Query;
use Exception;

class Mysql extends Builder
{
    /**
     * 查询表达式解析.
     *
     * @var array
     */
    protected $parser = [
        'parseCompare'     => ['=', '<>', '>', '>=', '<', '<='],
        'parseLike'        => ['LIKE', 'NOT LIKE'],
        'parseBetween'     => ['NOT BETWEEN', 'BETWEEN'],
        'parseIn'          => ['NOT IN', 'IN'],
        'parseExp'         => ['EXP'],
        'parseRegexp'      => ['REGEXP', 'NOT REGEXP'],
        'parseNull'        => ['NOT NULL', 'NULL'],
        'parseBetweenTime' => ['BETWEEN TIME', 'NOT BETWEEN TIME'],
        'parseTime'        => ['< TIME', '> TIME', '<= TIME', '>= TIME'],
        'parseExists'      => ['NOT EXISTS', 'EXISTS'],
        'parseColumn'      => ['COLUMN'],
        'parseFindInSet'   => ['FIND IN SET'],
    ];

    /**
     * SELECT SQL表达式.
     *
     * @var string
     */
    protected $selectSql = 'SELECT%DISTINCT%%EXTRA% %FIELD% FROM %TABLE%%PARTITION%%FORCE%%JOIN%%WHERE%%GROUP%%HAVING%%UNION%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * INSERT SQL表达式.
     *
     * @var string
     */
    protected $insertSql = '%INSERT%%EXTRA% INTO %TABLE%%PARTITION% SET %SET% %DUPLICATE%%COMMENT%';

    /**
     * INSERT ALL SQL表达式.
     *
     * @var string
     */
    protected $insertAllSql = '%INSERT%%EXTRA% INTO %TABLE%%PARTITION% (%FIELD%) VALUES %DATA% %DUPLICATE%%COMMENT%';

    /**
     * UPDATE SQL表达式.
     *
     * @var string
     */
    protected $updateSql = 'UPDATE%EXTRA% %TABLE%%PARTITION% %JOIN% SET %SET% %WHERE% %ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * DELETE SQL表达式.
     *
     * @var string
     */
    protected $deleteSql = 'DELETE%EXTRA% FROM %TABLE%%PARTITION%%USING%%JOIN%%WHERE%%ORDER%%LIMIT% %LOCK%%COMMENT%';

    /**
     * 生成查询SQL.
     * @param Query $query 查询对象
     * @param bool $one 是否仅获取一个记录
     * @return string
     * @throws Exception
     */
    public function select(Query $query, bool $one = false): string
    {
        $options = $query->getOptions();

        return str_replace(
            ['%TABLE%', '%PARTITION%', '%DISTINCT%', '%EXTRA%', '%FIELD%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%', '%UNION%', '%LOCK%', '%COMMENT%', '%FORCE%'],
            [
                $this->parseTable($query, $options['table']),
                $this->parsePartition($query, $options['partition']),
                $this->parseDistinct($query, $options['distinct']),
                $this->parseExtra($query, $options['extra']),
                $this->parseField($query, $options['field'] ?? []),
                $this->parseJoin($query, $options['join']),
                $this->parseWhere($query, $options['where']),
                $this->parseGroup($query, $options['group']),
                $this->parseHaving($query, $options['having']),
                $this->parseOrder($query, $options['order']),
                $this->parseLimit($query, $one ? '1' : $options['limit']),
                $this->parseUnion($query, $options['union']),
                $this->parseLock($query, $options['lock']),
                $this->parseComment($query, $options['comment']),
                $this->parseForce($query, $options['force']),
            ],
            $this->selectSql
        );
    }

    /**
     * Partition 分析.
     * @param Query        $query     查询对象
     * @param string|array $partition 分区
     * @return string
     */
    protected function parsePartition(Query $query, $partition): string
    {
        if ('' == $partition) {
            return '';
        }

        if (is_string($partition)) {
            $partition = explode(',', $partition);
        }

        return ' PARTITION (' . implode(' , ', $partition) . ') ';
    }
}