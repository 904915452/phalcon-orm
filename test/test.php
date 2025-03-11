<?php

use Dm\PhalconOrm\model\Model as OrmModel;
use Phalcon\Di\FactoryDefault;
use Dm\PhalconOrm\model\concern\SoftDelete;

require '../vendor/autoload.php';

class TestModel extends OrmModel
{
    use SoftDelete;

    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
//    protected $createTime = 'create_date';
//    protected $updateTime = 'update_date';
    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete = null;

    /**
     * 主键名称
     * @var string
     */
    protected string $pk = 'id';

    protected $id;

    protected $name;

    public function initialize(): void
    {
        parent::initialize();

        /**
         * 设置表名
         */
        $this->setSource('student');
    }
}


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
$db = new \Dm\PhalconOrm\DbManager();
$db->setConnector($di->getShared('db'));

$model = new TestModel();

$manager = $model->getModelsManager();
//$rt = $manager->executeQuery('update ' . TestModel::class . ' set name = "test" where id = :id:', ['id' => 15]);
//$rt = $manager->executeQuery('select * from ' . TestModel::class);
//$rt = $manager->executeQuery('DELETE FROM ' . TestModel::class . ' WHERE id = :id:', ['id' => 16]);
//var_dump($rt);

$data = TestModel::destroy(17);
var_dump($data);
exit;
