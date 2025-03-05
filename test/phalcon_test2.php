<?php

use Phalcon\Di\FactoryDefault;

require '../vendor/autoload.php';

class StudentModel extends \Phalcon\Mvc\Model
{

    public $id;

    public $name;

    public function initialize()
    {
        $this->setSource('student');
        $this->hasMany("id", "StudentScoreModel", "student_id"); // 修改为 "StudentScoreModel"
    }
}

class StudentScoreModel extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        $this->setSource('student_score');
        $this->belongsTo("student_id", "StudentModel", "id"); // 修改为 "StudentModel"
    }
}

// 模拟配置文件连接数据库
$di = new FactoryDefault();
$mysql1 = $di->setShared('db', function () {
//    $class = 'Dm\PhalconOrm\connector\Mysql';
    $class = \Phalcon\Db\Adapter\Pdo\Mysql::class;
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

//$data = StudentModel::findFirst(['conditions' => "name = :name:", "bind" => ["name" => "张三"]]);

//var_dump($data->id);
//var_dump($data->getStudentScoreModel());exit;
//var_dump($data->toArray(), $data->getStudentScoreModel()->toArray());exit;

$model = new StudentModel();
$model->assign(['name' => 'liam',
    'sex' => '男']);

var_dump($model->name, $model->sex);
exit;

$rt = $model->save();
var_dump($rt);
exit;