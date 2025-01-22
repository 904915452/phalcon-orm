# Phalcon的Orm操作重新封装

主要用于补全phalcon的各项没有的orm操作。

# 数据库
## 模型

```
每个模型需要继承 \Dm\PhalconOrm\model\Model 该类

$di = new FactoryDefault();  
$mysql1 = $di->setShared('db', function () {  
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

使用新的Mysql连接类进行连接数据库
```

## 查询构造器
### 链式操作

> 两种调用方式

```
$model = new TestModel;  
$data = $model->where("subject","数学")->where("score",">",80)->select();  
```

```
$data = TestModel::where("subject","数学")->where("score",">",80)->select();
```

----
##### where()

> 支持多种调用方式

1. 关联数组

- 例1 普通调用
```
$model->where(["name" => "王五", "subject" => "数学"])->fetchSql()->select();


SELECT * FROM student_score WHERE  name = '王五'  AND subject = '数学'
```

2. 索引数组

```
$model->where([  
    ["score", ">", "80"],  
    ["subject", "=", "数学"]  
])->select();

SELECT * FROM student_score WHERE  score > 80  AND subject = '数学'
```

3. 普通调用

```
$model->where("subject", "数学")->where("score", ">", 80)->select();

SELECT * FROM student_score WHERE  subject = '数学'  AND score > 80
```

5. 字符串

```
$model->whereRaw("score > 80  AND subject = '数学'")->select();

$model->whereRaw("score > :score  AND subject = :subject", [  
    "subject" => '数学',  
    "score" => 80  
])->select();

SELECT * FROM student_score WHERE  ( score > '80'  AND subject = '数学' )

```

---

##### table()

```

```

----


