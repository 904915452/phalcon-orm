<?php

use Phalcon\Di\FactoryDefault;

require '../vendor/autoload.php';

class StudentModel extends \Dm\PhalconOrm\model\Model
{
    public function initialize(): void
    {
        parent::initialize();
        $this->setSource('student');
//        $this->hasMany("id", "StudentScoreModel", "student_id", ['alias' => 'scores']); // 修改为 "StudentScoreModel"
        $this->hasMany("id", "StudentScoreModel", "student_id"); // 修改为 "StudentScoreModel"
    }
}

class StudentScoreModel extends \Dm\PhalconOrm\model\Model
{
    public function initialize(): void
    {
        parent::initialize();

        $this->setSource('student_score');
        $this->belongsTo("student_id", "StudentModel", "id"
        ); // 修改为 "StudentModel"
    }
}

// 模拟配置文件连接数据库
$di = new FactoryDefault();
// 必须显式注入服务
$di->setShared('modelsManager', function () use ($di){
    $manage = new \Phalcon\Mvc\Model\Manager();
    $manage->setDI($di);
    return $manage;
});

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

$data = StudentModel::whereIn("id",[1,2])->select();
foreach ($data as $item) {
    var_dump($item->getStudentScoreModel()->toArray());
}
//var_dump($data->readAttribute("id"));
//var_dump($data->toArr());exit;
//var_dump($data->getRelated('scores'),$data->scores);


//var_dump($data->toArr(),$data->getStudentScoreModel());
//var_dump($data->toArray(), $data->getStudentScoreModel()->toArray());
exit;