<?php

use Phalcon\Di\Di;
use Phalcon\Di\FactoryDefault;

require '../vendor/autoload.php';


class StudentModel extends \Dm\PhalconOrm\model\Model
{
    public function initialize(): void
    {
        $this->setSource('student');
        $this->hasMany("id", StudentScoreModel::class, "student_id", ['alias' => 'scores']);
    }
}

class StudentScoreModel extends \Dm\PhalconOrm\model\Model
{
    public function initialize(): void
    {
        $this->setSource('student_score');
        $this->belongsTo("student_id", StudentModel::class, "id", ['alias' => 'student']);
    }
}

// 模拟配置文件连接数据库
$di = new FactoryDefault();
// 必须显式注入服务
$di->setShared('modelsManager', function () use ($di) {
    $manage = new \Phalcon\Mvc\Model\Manager();
    $manage->setDI($di);
    return $manage;
});

Di::setDefault($di);


$mysql1 = $di->setShared('db', function () {
    $class = 'Dm\PhalconOrm\connector\Mysql';
    $params = [
        'host' => 'host.docker.internal',
        'username' => 'root',
        'password' => 'root',
        'dbname' => 'test',
        'charset' => 'utf8mb4',
        "options" => [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_STRINGIFY_FETCHES => false, PDO::ATTR_EMULATE_PREPARES => false],
        'logQueries' => true,
        'fields_strict' => true, // 是否开启字段严格检查 某个字段不存在时，是否抛出异常
    ];
    return new $class($params);
});

$db = new \Dm\PhalconOrm\DbManager();
$db->setConnector($di->getShared('db'));

$data = StudentModel::where("id", 1)->first();
if ($data) {
    var_dump($data->toArr());         // 查看主模型数据
    var_dump($data->scores->toArray());  // 查看
    //关联数据
}