<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use Webman\Route;

class AnnotationProvider
{
    public static function start()
    {
        $annotationClasses = self::scanFile();
        $routes = Route::getRoutes();
        $exist_list = [];
        foreach ($routes as $tmp_route) {
            $exist_list[$tmp_route->getPath()] = $tmp_route->getMethods();
        }
        /**
         * 1、读取类的注解，如：路径前缀，资源路由，中间件
         * 2、读取方法注解，继承类注解的中间件，路径前缀
         * 3、保存已定义路由到数组，如果遇到定义相同路由情况下，抛出异常且跳过当条定义
         */
        foreach ($annotationClasses as $class_name) {

            $class = new ReflectionClass($class_name);
            config("plugin.shayvmo.webman-annotations.annotation.ignored");
            foreach (config("plugin.shayvmo.webman-annotations.annotation.ignored") as $v) {
                AnnotationReader::addGlobalIgnoredName($v);
            }
            $class_name = $class->name;
            $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
            $reader = new AnnotationReader();
            /** @var  $class_annotations *注解的读取类 */
            $class_annotations = $reader->getClassAnnotations($class);
            echo "---------开始---------------\n";
            var_export($class_annotations);
            echo "----------结束--------------\n";


            $class_resource_annotation = $reader->getClassAnnotation($class, \App\annotations\ResourceMapping::class);
//            var_export($class_resource_annotation);

            return [];
//            /** @var  $classMiddlewareAnnotation *单个中间件注解参数 */
//            $classMiddlewareAnnotation = $reader->getClassAnnotation($class, Middleware::class);
//            /** @var  $classMiddlewareAnnotations *多个个中间件注解参数 */
//            $classMiddlewareAnnotations = $reader->getClassAnnotation($class, Middlewares::class);
//
//            if (!empty($class_resource_anno)) {
////            Route::resource($class_resource_anno->path,
////                $class_name, ['index', 'store', 'show', 'update', 'destroy']);
//            }
//
//            /** @var  $item *设置路由 */
//            foreach ($methods as $item) {
//                /** @var  $action */
//                $action = $item->name;
//                if (in_array($action, ['__construct', '__destruct'])) {
//                    continue;
//                }
//                /** @var  $methodAnnotation *获取@requestmapping的参数 */
//                $methodAnnotation = $reader->getMethodAnnotation($item, RequestMapping::class);
//                /** @var  $middlewareAnnotation *单个中间件注解参数 */
//                $middlewareAnnotation = $reader->getMethodAnnotation($item, Middleware::class);
//                /** @var  $middlewareAnnotation *多个个中间件注解参数 */
//                $middlewaresAnnotation = $reader->getMethodAnnotation($item, Middlewares::class);
//                if (empty($methodAnnotation)) {
//                    continue;
//                }
//                $middlewares = [];
//                if (!empty($middlewareAnnotation)) {
//                    foreach ($middlewareAnnotation as $obj) {
//                        $middlewares = $obj[0]['value'] ?? [];
//                    }
//                }
//                if (!empty($middlewaresAnnotation)) {
//                    foreach ($middlewaresAnnotation->middlewares as $objs) {
//                        $middlewares[] = $objs->middleware[0]['value'] ?? "";
//                    }
//                }
//                Route::add($methodAnnotation->methods, $methodAnnotation->path, [$class_name, $action])->middleware($middlewares);
//
//            }
        }

    }

    public static function scanFile()
    {

        $suffix = config('app.controller_suffix', '');
        $suffix_length = strlen($suffix);

        $scanFolders = config("plugin.shayvmo.webman-annotations.annotation.include_paths");

        foreach ($scanFolders as $scanFolder) {
            $dir_iterator = new \RecursiveDirectoryIterator(app_path("$scanFolder/controller"));
            $iterator = new \RecursiveIteratorIterator($dir_iterator);
            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                /** 忽略目录和非php文件 */
                if ($file->isDir() || $file->getExtension() !== 'php') {
                    continue;
                }

                $file_path = str_replace('\\', '/', $file->getPathname());

                /**  只处理带 controller_suffix 后缀的 */
                if ($suffix_length && substr($file->getBaseName('.php'), -$suffix_length) !== $suffix) {
                    continue;
                }

                // 根据文件路径是被类名
                /** @var  $class_name *根据文件路径获取类名 */
                $class_name = str_replace('/', '\\', substr(substr($file_path, strlen(base_path())), 0, -4));

                if (!class_exists($class_name)) {
                    continue;
                }

                yield $class_name;
            }
        }

    }
}
