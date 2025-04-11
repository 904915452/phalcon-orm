<?php
require '../vendor/autoload.php';

use Dm\PhalconOrm\model\Model as OrmModel;
use Phalcon\Di\FactoryDefault;
use Dm\PhalconOrm\model\concern\SoftDelete;

try {

    class TestModel extends OrmModel
    {
//        use SoftDelete;

//        protected $autoWriteTimestamp = true;

        // 定义时间戳字段名
//    protected $createTime = 'create_date';
//    protected $updateTime = 'update_date';
//        protected $deleteTime = 'delete_time';
//        protected $defaultSoftDelete = null;

        /**
         * 主键名称
         * @var string
         */
        protected $pk = 'id';

        public $id;

        public $name;

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
            'username' => 'root57',
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

//    var_dump(TestModel::where("id", "in", [1, 2, 3])->select()->toArr());
//    var_dump(TestModel::whereIn("id", [1, 2, 3])->select()->toArr());
//    var_dump(TestModel::where(["id" => [1, 2, 3]])->select()->toArr());

//    $model = new TestModel();
//    $model->name = 'wangyuchang';
//    var_dump($model->save());

//    $rt = TestModel::whereNull("sex")->update(["sex" => "女"]);

//    $rt = TestModel::destroy(20); // 软删除

//    $rt = TestModel::where("id",21)->delete();

    $rt = TestModel::order("name","desc")->limit(5)->select()->toArr();
    var_dump($rt);


} catch (\Throwable $e) {
    var_dump($e->getFile() . $e->getMessage() . $e->getLine());
    exit;
}
