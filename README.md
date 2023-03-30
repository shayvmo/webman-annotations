# webman 框架 注解路由插件

> 使用了 doctrine/annotations 包来对代码内的注解进行解析。
>
> 您可以直接在控制器类或类方法定义注解，实现路由定义。

webman框架插件地址：[https://www.workerman.net/plugin/115](https://www.workerman.net/plugin/115)

站在巨人的肩膀可以看到更远，感谢 [https://www.workerman.net/plugin/52](https://www.workerman.net/plugin/52) 的启发。

## 安装

```shell
composer require shayvmo/webman-annotations
```
## 使用

### 配置文件

```php
<?php
// config/plugin/shayvmo/webman-annotations/annotation.php
return [
    // 注解扫描路径, 只扫描应用目录下已定义的文件夹，例如： app/admin/controller 及其下级目录
    'include_paths' => [
        'admin'
    ],
    // requestMapping 允许的请求method
    'allow_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'],
    // 忽略解析的注解名称，适用于 php7 使用 doctrine/annotations 解析
    'ignored' => [
        "after", "afterClass", "backupGlobals", "backupStaticAttributes", "before", "beforeClass", "codeCoverageIgnore*",
        "covers", "coversDefaultClass", "coversNothing", "dataProvider", "depends", "doesNotPerformAssertions",
        "expectedException", "expectedExceptionCode", "expectedExceptionMessage", "expectedExceptionMessageRegExp", "group",
        "large", "medium", "preserveGlobalState", "requires", "runTestsInSeparateProcesses", "runInSeparateProcess", "small",
        "test", "testdox", "testWith", "ticket", "uses" , "datetime",
    ]
];

```

### 一、中间件注解

<span style="color: red">注：方法会继承类定义的中间件。</span>

类和方法通用，参数中间件类名，单个中间件传入字符串，多个中间件传入字符串数组。

```
use Shayvmo\WebmanAnnotations\Annotations\Middleware;
use App\third\middleware\SignatureCheckA;

// php74
/**
 * @Middleware(
 *     \App\third\middleware\SignatureCheck::class,
 * )
 */
 
/**
 * @Middleware({
 *   SignatureCheckA::class,
 *   \App\third\middleware\SignatureCheck::class,
 * })
 */

// php8注解
// 单个中间件
#[Middleware(LimitTrafficMiddleware::class)]
// 多个
#[Middleware([LimitTrafficMiddleware::class, \App\third\middleware\SignatureCheck::class])]

```

### 二、类注解

类注解有控制器注解` @RestController `和资源路由` @ResourceMapping `。
资源路由和` webman `框架原有的资源路由一致。参考：[webman路由](https://www.workerman.net/doc/webman/route.html)

#### 控制器注解

```
use Shayvmo\WebmanAnnotations\Annotations\RestController;
```

` @RestController `控制器注解，只有一个参数` prefix `,表示整个控制器的路由路径前缀，方法路由路径都会拼接该前缀。
传参可以省略键名。

- `@RestController("/a")`
- `@RestController(prefix="/a")`
  
php8注解
- `#[RestController("/test1")]`
- `#[RestController(path: "/test2")]`


#### 资源路由注解

```
use Shayvmo\WebmanAnnotations\Annotations\ResourceMapping;
```

` @ResourceMapping `资源路由注解，有` path ` 和 ` allow_methods `两个参数
`path`表示资源路由的路径，`allow_methods`为指定的资源方法数组，不传指定资源方法时，使用全部资源方法
`path`传参可以省略键名。

- `@ResourceMapping(path="/dddd", allow_methods={"index", "show"})`
- `@ResourceMapping("/dddd", allow_methods={"index", "show"})` 

php8注解
- `#[ResourceMapping("/test", allow_methods: ["index", "show"])]`
- `#[ResourceMapping(path: "/test2", allow_methods: ["index", "show"])]`

<span style="color: red">注：如果定义了资源路由，会自动忽略类同名方法的方法注解。</span>

附：资源路由方法对照

|请求方法|路径|类方法|
|---|---|---|
|GET|/test|index|
|GET|/test/create|create|
|POST|/test|store|
|GET|/test/{id}|show|
|GET|/test/{id}/edit|edit|
|PUT|/test/{id}|update|
|DELETE|/test/{id}|destroy|
|PUT|/test/{id}/recovery|recovery|

### 三、方法注解

方法注解主要是`@RequestMapping` 以及 `@GetMapping`、`@PostMapping`、`@PutMapping`、`@DeleteMapping` 四个便捷注解。
定义路由路径 `path` 和请求方法` methods `。两个参数均可以传入字符串或数组。
例如`path`传入数组时，表示多个请求路由路径。`methods`传入数组时，表示多个请求方法。

<span style="color: red">注：便捷注解传入路由路径`path`即可，可以省略键名`path`，无需传入`methods`</span>

- `@RequestMapping(path={"/dddd", "/dddd1"}, methods={"get", "post"})`
- `@GetMapping(path={"/get","/get1"})`
- `@GetMapping({"/get","/get1"})`
- `@PostMapping(path="/post")`
- `@PutMapping(path="/put")`
- `@DeleteMapping(path="/delete")`

php8注解
- `#[RequestMapping("/test1", methods: "get")]`
- `#[RequestMapping(["/test1","/test11"], methods: ["get", "post"])]`
- `#[GetMapping(["/get", "/get1"])]`
- `#[PostMapping(path: "/post")]`
- `#[PutMapping(path: "/put")]`
- `#[DeleteMapping("/delete")]`


```
// 方法注解
use Shayvmo\WebmanAnnotations\Annotations\RequestMapping;
use Shayvmo\WebmanAnnotations\Annotations\GetMapping;
use Shayvmo\WebmanAnnotations\Annotations\PostMapping;
use Shayvmo\WebmanAnnotations\Annotations\PutMapping;
use Shayvmo\WebmanAnnotations\Annotations\DeleteMapping;
```

### 四、示例

```php
<?php

declare (strict_types=1);

namespace App\third\controller;

use Shayvmo\WebmanAnnotations\Annotations\RestController;
use Shayvmo\WebmanAnnotations\Annotations\DeleteMapping;
use Shayvmo\WebmanAnnotations\Annotations\GetMapping;
use Shayvmo\WebmanAnnotations\Annotations\Middleware;
use Shayvmo\WebmanAnnotations\Annotations\PostMapping;
use Shayvmo\WebmanAnnotations\Annotations\PutMapping;
use Shayvmo\WebmanAnnotations\Annotations\RequestMapping;
use Shayvmo\WebmanAnnotations\Annotations\ResourceMapping;

use App\third\middleware\SignatureCheck;

use support\Request;
use Tinywan\LimitTraffic\Middleware\LimitTrafficMiddleware;

/**
 * @RestController("/test")
 * @ResourceMapping("/dddd", allow_methods={"index", "show"})
 * @Middleware(SignatureCheck::class)
 */
class ATest
{
    public function index()
    {
        // 
        return 'Test/index';
    }

    public function show(Request $request, $id)
    {
        return "Test/show $id";
    }

    /**
     * @GetMapping("/test")
     * @Middleware(SignatureCheck::class)
     */
    public function get()
    {
        return 'Test/get';
    }

    /**
     * @RequestMapping(methods={"get", "post"}, path="/test1")
     * @Middleware({
     *     LimitTrafficMiddleware::class,
     * })
     */
    public function test()
    {
        return 'Test/test';
    }

    /**
     * @PostMapping("/post")
     */
    public function post()
    {
        return 'Test/post';
    }

    /**
     * @PutMapping("/put")
     */
    public function put()
    {
        return 'Test/put';
    }

    /**
     * @DeleteMapping("/delete")
     */
    public function delete()
    {
        return 'Test/delete';
    }
}

```

五、更新日志

- v1.1.0

  2023-03-30，增加php8原生注解支持

- v1.0.1

  2023-03-27，修复发现的bug

- v1.0.0

  2023-03-27，发布1.0.0版本
