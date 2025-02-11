<?php

use Dm\PhalconOrm\model\Model as OrmModel;
use Phalcon\Di\FactoryDefault;

require '../vendor/autoload.php';

class TestModel extends OrmModel
{
    /**
     * 主键名称
     * @var string
     */
    protected string $pk = 'id';

    public function initialize(): void
    {
        parent::initialize();

        /**
         * 设置表名
         */
        $this->setSource('student_score');
    }

    public function getNameZhAttr($value,$data)
    {
        return $data["name"]."zxczxczxc";
    }
}

/**
 * 用于写入
 * 除了查询操作之外，field方法还有一个非常重要的安全功能--字段合法性检测。
 */


/**
 * 如果用于insertAll方法的话，则可以分批多次写入，每次最多写入limit方法指定的数量。
 *
 * Db::table('user')
 * ->limit(100)
 * ->insertAll($userList);
 */

/**
 * cache方法 可以缓存查询结果，下次查询时直接从缓存中读取，而不需要再次查询数据库。
 */


// 模拟配置文件连接数据库
$di = new FactoryDefault();
$mysql1 = $di->setShared('db', function () {
    $class = 'Dm\PhalconOrm\connector\Mysql';
    $params = [
        'host' => 'host.docker.internal',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'test',
        'charset' => 'utf8mb4',
        "options" => [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_STRINGIFY_FETCHES => false, PDO::ATTR_EMULATE_PREPARES => false],
        'fields_strict' => true, // 是否开启字段严格检查 某个字段不存在时，是否抛出异常
    ];
    return new $class($params);
});


// 实例
$model = new TestModel;

$db = new \Dm\PhalconOrm\DbManager();
$db->setConnector($di->getShared('db'));

$db->transaction(function () {
    $data = (new TestModel)->save(["name" => "王五", "subject" => "计算机", "score" => 71, "class" => 182112,"asdasd" => "zxc"]);
    var_dump($data);
});

exit;
