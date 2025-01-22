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
}


// 模拟配置文件连接数据库
$di = new FactoryDefault();
$mysql1 = $di->setShared('db', function () {
//    $class = 'Phalcon\Db\Adapter\Pdo\Mysql';
    $class = 'Dm\PhalconOrm\connector\Mysql';
    $params = [
        'host' => 'host.docker.internal',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'test',
        'charset' => 'utf8mb4',
        "options" => [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_STRINGIFY_FETCHES => false, PDO::ATTR_EMULATE_PREPARES => false]
    ];
    return new $class($params);
});

// 实例
$model = new TestModel;

// 索引数组
$data = $model->where([
    ["score", ">", "80"],
    ["subject", "=", "数学"]
])->select();

//$data = $model->db()->table("test")->select();

var_dump($data);
exit;
