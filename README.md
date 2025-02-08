# Phalcon的Orm操作重新封装

主要用于补全phalcon的各项没有的orm操作。




# 数据库

## 连接数据库

```
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
        
        // 新增配置项
        'fields_strict' => true, // 是否开启字段严格检查 某个字段不存在时，是否抛出异常  
    ];  
    return new $class($params);  
});
```



### db连接：
```
例：
$db = new \Dm\PhalconOrm\DbManager();  
$db->setConnector($di->getShared('db'));
$data = $db->table("student_score")->save(["name" => "王五20222", "subject" => "计算机", "score" => 72, "class" => 182112, "id" => 20]);
```

### 模型：

> 参考 模型 章节


## 查询构造器

### 查询数据

#### 查询单个数据 first()

```
$db->table("student_score")->where("subject","数学")->first()

SELECT * FROM student_score WHERE  subject = '数学' LIMIT 1
```

#### 查询多条数据 select()

```
$db->table("student_score")->select();

SELECT * FROM student_score
```

#### 值和列查询

> 值查询 value()

```
$db->table("student_score")->where("name","张三")->where("subject","数学")->value("score");

SELECT score FROM student_score WHERE  name = '张三'  AND subject = '数学' LIMIT 1
```

> 列查询 column()

- 查询单个字段
```
$db->table("student_score")->column("subject");

SELECT subject FROM student_score
```

- 查询两个字段
```
$data = $model->column("name","class");

会自动将第二个字段作为关联数组的key，以一维数组返回。

array(2) {
  [182111]=>  string(6) "张三"
  [182112]=>  string(6) "王五"
}
```

### 添加数据
- insert() 添加数据

```
$db->table("student_score")->insert(["name" => "张三", "subject" => "计算机", "score" => 62, "class" => 182111]);

INSERT INTO student_score SET name = '张三' , subject = '计算机' , score = 62 , class = '182111'
```

- duplicate()  主要用于某数据 存在则更新 不存在则创建 的逻辑

```
$db->table("student_score")
->duplicate(['name' => "张三", "subject" => "计算机", "score" => 62])
->insert(["name" => "张三", "subject" => "计算机", "score" => 62, "class" => 182111, "id" => 9]);


INSERT INTO student_score SET name = '张三' , subject = '计算机' , score = 62 , class = '182111' , id = 9  ON DUPLICATE KEY UPDATE name = '张三' , subject = '计算机' , score = '62'

要插入的表中需要有一个唯一键，例如 主键，当唯一键冲突时，会执行 ON DUPLICATE KEY UPDATE 后面的语句
```

例：
原数据：

| id  | name | subject | score | class   |
| --- | ---- | ------- | ----- | ------- |
| 9   | 张三   | 计算机     | 60    | 1821111 |

```
$db->table("student_score")
->duplicate(['name' => "张三2", "subject" => "计算机", "score" => 62])
->insert(["name" => "张三", "subject" => "计算机", "score" => 62, "class" => 182111, "id" => 9]);
```

> 当唯一键冲突时，会对冲突的那条数据，根据duplicate()里面的数据，执行更新。

执行后的结果：

| id  | name | subject | score | class   |
| --- | ---- | ------- | ----- | ------- |
| 9   | 张三   | 计算机     | 62    | 1821111 |


- save()

```
$model->save(["name" => "王五", "subject" => "计算机", "score" => 71, "class" => 182112,"asdasd" => "zxc"]);

INSERT INTO student_score SET name = '王五' , subject = '计算机' , score = 71 , class = '182112'
```

> 同理：db也是可以的

```
$db->table("student_score")->save(["name" => "王五222", "subject" => "计算机", "score" => 733, "class" => 182112]);
```

- replace()

| 变更前 | 19  | 王五  | 计算机 | 72  | 182112 |
| --- | --- | --- | --- | --- | ------ |
| 变更后 | 19  | 王五  | 计算机 | 71  | 182112 |

```
$data = $model->replace()->save(["name" => "王五", "subject" => "计算机", "score" => 71, "class" => 182112,"id" => 19]);
```

- insertGetId()

```
$id = $model->insertGetId(["name" => "王五", "subject" => "计算机", "score" => 72, "class" => 182112]);

返回自增ID
```

- insertAll() 批量插入

```
$model->insertAll([
["name" => "王五", "subject" => "计算机", "score" => 72, "class" => 182112],["name" => "王五", "subject" => "计算机", "score" => 72, "class" => 182112],["name" => "王五", "subject" => "计算机", "score" => 72, "class" => 182112]
]);

返回插入条数
```

```
$data = $db->table("student_score")->replace()->insertAll([  
    ["name" => "王五20", "subject" => "计算机", "score" => 72, "class" => 182112, "id" => 20],  
    ["name" => "王五21", "subject" => "计算机", "score" => 72, "class" => 182112, "id" => 21],  
    ["name" => "王五22", "subject" => "计算机", "score" => 72, "class" => 182112, "id" => 22]  
]);
也可以用DB这样调用，替换数据
```

> 确保要批量添加的数据字段是一致的


### 更新数据

- save()
```
$data = $db->table("student_score")->save(["name" => "王五20222", "subject" => "计算机", "score" => 72, "class" => 182112, "id" => 20]);


$model = TestModel::first(17);  
$model->name = "张三1zxczxc";  
$model->save();
```

---

- update()

```
$data = $db->table("student_score")->where("id",">",10)->where("id","<","20")->update(["remarks" => "测试机1111"]);

UPDATE student_score  SET remarks = '测试机1111'  WHERE  id > 10  AND id < 20
```


### 删除数据

delete()

> 返回影响的行数（删除了几条数据）

```
$affectRows = $db->table("student_score")->whereIn("id",[4,5])->delete();

DELETE FROM student_score WHERE  id IN (4,5)
```

---


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
$model->where(["name" => "王五", "subject" => "数学"])->select();


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

```
$model->where([["subject", "like", "语%"]])->select();

SELECT * FROM student_score WHERE  subject LIKE '语%'
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

> 选择表

```
// 1、连接数据库
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

// 2、设置连接对象
$db = (new \Dm\PhalconOrm\DbManager())->setConnector($di->getShared('db'));

// 3、选择表
$data = $db->table("user")->select();

// SELECT * FROM user
```

----

##### alias()

> 重命名表名称

```
alias多用于配合join等使用

$db->table("student")->field("sc.class,s.name,s.number")->alias("s")->join("student_score sc","sc.name = s.name")->select();

等同于

SELECT sc.class,s.name,s.number FROM student s INNER JOIN student_score sc ON sc.name = s.name
```

---
##### field()

> 限制查询字段

- 设置查询字段｜字段重命名

1. 字符串写法
```
$model->field("id,name,class as classNum")->select();

SELECT id,name,class as classNum FROM student_score
```

2. 数组写法
```
$model->field(["id","name","class" => "classNum"])->select();

SELECT id,name,class AS classNum FROM student_score
```

- 显式调用所有字段
```
$model->field(true)->select();

SELECT id,name,subject,score,class FROM student_score
```

- 用于insert
```

```

--- 
##### limit()

> 限制查询条数

1. 限制查询条数
```
$model->limit(3)->select();

SELECT * FROM student_score LIMIT 3
```

2. 从第 * 条开始查询 * 条数据
```
$model->limit(3, 2)->select();

SELECT * FROM student_score LIMIT 3,2
```

---
##### order()

> 排序

- 升序
```
$model->limit(3)->order("id")->select();
默认为asc

SELECT * FROM student_score ORDER BY id LIMIT 3
```

```
$model->limit(3)->order("id","asc")->select();

SELECT * FROM student_score ORDER BY id ASC LIMIT 3
```

- 降序
```
$model->limit(3)->order("id","desc")->select();

SELECT * FROM student_score ORDER BY id DESC LIMIT 3
```

- 数组形式
```
$model->order(['class' => 'asc','id'=>'desc'])->select();

SELECT * FROM student_score ORDER BY class ASC,id DESC


$model->order(['class','id'=>'desc'])->select();

SELECT * FROM student_score ORDER BY class,id DESC
```

- 在排序中使用函数
```
$model->orderRaw("field('id','class')")->select();

SELECT * FROM student_score ORDER BY field('id','class')
```

---
##### group()

> 分组

- 单个分组
```
$model->field("class,count(id) as studentTotal")->group("class")->select();

SELECT class,count(id) as studentTotal FROM student_score GROUP BY class
```

- 多个字段分组
```
$model->field("class,count(id) as studentTotal")->group("class,subject")->select();

SELECT class,count(id) as studentTotal FROM student_score GROUP BY class,subject
```

---
##### having()

> 针对group后的筛选条件

```
$model->field("name,sum(score) as allScore")->group("name")->having("allScore >= 150")->select();

SELECT name,sum(score) as allScore FROM student_score GROUP BY name HAVING allScore >= 150
```

---

##### join()

> 连表

> - **INNER JOIN**: 等同于 JOIN（默认的JOIN类型）,如果表中有至少一个匹配，则返回行
> - **LEFT JOIN**: 即使右表中没有匹配，也从左表返回所有的行
> - **RIGHT JOIN**: 即使左表中没有匹配，也从右表返回所有的行
> - **FULL JOIN**: 只要其中一个表中存在匹配，就返回行

说明：
```
join ( mixed join [, mixed $condition = null [, string $type = 'INNER']] )
leftJoin ( mixed join [, mixed $condition = null ] )
rightJoin ( mixed join [, mixed $condition = null ] )
fullJoin ( mixed join [, mixed $condition = null ] )
```

- join()
```
$model->field("sc.class,s.name,s.number")->alias("sc")->join("student s","sc.name = s.name");


SELECT sc.class,s.name,s.number FROM student_score sc INNER JOIN student s ON sc.name = s.name
```

- leftJoin()
```
$model->field("sc.class,s.name,s.number")->alias("sc")->leftJoin("student s","sc.name = s.name");


SELECT sc.class,s.name,s.number FROM student_score sc LEFT JOIN student s ON sc.name = s.name
```

- rightJoin()
```
$model->field("sc.class,s.name,s.number")->alias("sc")->rightJoin("student s","sc.name = s.name");


SELECT sc.class,s.name,s.number FROM student_score sc RIGHT JOIN student s ON sc.name = s.name
```

- fullJoin()
```
$model->field("sc.class,s.name,s.number")->alias("sc")->fullJoin("student s","sc.name = s.name")->select();


SELECT sc.class,s.name,s.number FROM student_score sc FULL JOIN student s ON sc.name = s.name
```

举例：
```
也可以为数组形式
$model->field("sc.class,s.name,s.number")->alias("sc")->join(["student" => "s"], "sc.name = s.name")->select();
```

> 表名也可以是一个子查询

```
$sql = (new \Dm\PhalconOrm\DbManager)->setConnector($di->getShared("db"))->table("student")->buildSql();  

$data = $model->field("sc.class,s.name,s.number")->alias("sc")->join([$sql => "s"], "sc.name = s.name")->select();

实际sql：

SELECT sc.class,s.name,s.number FROM student_score sc INNER JOIN ( SELECT * FROM student ) s ON sc.name = s.name
```

---
##### union()   unionAll()

> UNION操作用于合并两个或多个 SELECT 语句的结果集。

- sql写法
```
$db->table("test")->field("name_cn")->union('select name from student')->union('select nick_name as name from user')->select();

SELECT name_cn FROM test UNION ( select name from student ) UNION ( select nick_name as name from user );
```

- 闭包写法
```
$db->table("test")->field("name_cn")->union(function($query){  
    $query->field("name")->table('student');  
})->union(function($query){  
    $query->field("nick_name as name")->table('user');  
})->select();
```

> 支持unionAll()

```
$data = $db->table("test")->field("name_cn")->unionAll(function($query){  
    $query->field("name")->table('student');  
})->unionAll(function($query){  
    $query->field("nick_name as name")->table('user');  
})->select();

SELECT name_cn FROM test UNION ALL ( SELECT name FROM student ) UNION ALL ( SELECT nick_name as name FROM user )
```

```
$db->table("test")->field("name_cn")->unionAll('select name from student')->unionAll('select nick_name as name from user')->select();
```

---
##### distinct()

>返回唯一不同的值  distinct()参数默认值是true

```
$db->table("student_score")->distinct(true)->field('subject')->select();

或者

$db->table("student_score")->distinct()->field('subject')->select();


SELECT DISTINCT  subject FROM student_score
```

---
##### lock()

> 锁

- 例1:
```
$db->table("student_score")->where("class","182111")->lock(true)->first();

SELECT * FROM student_score WHERE  class = '182111' LIMIT 1   FOR UPDATE
```

- 例2: 

>lock方法支持传入字符串用于一些特殊的锁定要求

```
$db->table("student_score")->where("class","182111")->lock('lock in share mode')->first();

SELECT * FROM student_score WHERE  class = '182111' LIMIT 1   lock in share mod
```

---
##### fetchSql()

> 用于直接返回SQL而不是执行查询，适用于任何的CURD操作方法。

```
$db->table("student_score")->where("class","182111")->fetchSql()->first()
```

---

toArr()

> 转化为数组

```
$model = TestModel::first(17);
var_dump($model->toArr())

array(6) {
  ["id"]=>
  int(17)
  ["name"]=>
  string(13) "张三1zxczxc"
  ["subject"]=>
  string(9) "计算机"
  ["score"]=>
  int(62)
  ["class"]=>
  string(6) "182111"
  ["remarks"]=>
  string(13) "测试机1111"
}
```

---













# 模型

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
 