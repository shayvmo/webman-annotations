<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use Shayvmo\WebmanAnnotations\Annotations\Controller;
use Shayvmo\WebmanAnnotations\Annotations\DeleteMapping;
use Shayvmo\WebmanAnnotations\Annotations\GetMapping;
use Shayvmo\WebmanAnnotations\Annotations\Middleware;
use Shayvmo\WebmanAnnotations\Annotations\PostMapping;
use Shayvmo\WebmanAnnotations\Annotations\PutMapping;
use Shayvmo\WebmanAnnotations\Annotations\RequestMapping;
use Shayvmo\WebmanAnnotations\Annotations\ResourceMapping;
use Webman\Route;

class AnnotationProvider
{
    public static function start()
    {
        $annotationClasses = self::scanFile();
        $formatData = self::formatData($annotationClasses);
        foreach ($formatData as $item) {
            $method = $item['method'];
            if (is_array($method)) {
                Route::add($method, $item['path'], [$item['className'], $item['action']])->middleware($item['middleware']);
            } else if ($method === 'resource') {
                Route::group('', function () use ($item) {
                    Route::resource($item['path'], $item['className'], $item['allowMethods']);
                })->middleware($item['middleware']);
            } else {
                Route::$method($item['path'], [$item['className'], $item['action']])->middleware($item['middleware']);
            }
        }
    }

    private static function scanFile()
    {
        $suffix = config('app.controller_suffix', '');
        $suffixLength = strlen($suffix);
        $scanFolders = config("plugin.shayvmo.webman-annotations.annotation.include_paths");
        foreach ($scanFolders as $scanFolder) {
            $dirIterator = new \RecursiveDirectoryIterator(app_path("$scanFolder/controller"));
            $iterator = new \RecursiveIteratorIterator($dirIterator);
            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->isDir() || $file->getExtension() !== 'php') {
                    continue;
                }

                $filePath = str_replace('\\', '/', $file->getPathname());

                if ($suffixLength && substr($file->getBaseName('.php'), -$suffixLength) !== $suffix) {
                    continue;
                }
                $className = str_replace('/', '\\', substr(substr($filePath, strlen(base_path())), 0, -4));

                if (!class_exists($className)) {
                    continue;
                }

                yield $className;
            }
        }

    }

    private static function formatData($annotationClasses)
    {
        config("plugin.shayvmo.webman-annotations.annotation.ignored");
        foreach (config("plugin.shayvmo.webman-annotations.annotation.ignored") as $v) {
            AnnotationReader::addGlobalIgnoredName($v);
        }
        $tempClassAnnotations = [];
        $annotationReader = new AnnotationReader();
        foreach ($annotationClasses as $annotationClass) {
            $class = new ReflectionClass($annotationClass);
            $reader = clone $annotationReader;

            $resourceMatch = false;
            $classAllowMethods = [];
            $className = $class->name;


            $classPrefix = '';
            /** @var Controller $classControllerAnnotation */
            $classControllerAnnotation = $reader->getClassAnnotation($class, Controller::class);
            if ($classControllerAnnotation) {
                $classPrefix = $classControllerAnnotation->getPrefix();
            }

            $classMiddlewares = [];
            /** @var Middleware $classMiddlewareAnnotation */
            $classMiddlewareAnnotation = $reader->getClassAnnotation($class, Middleware::class);
            if ($classMiddlewareAnnotation) {
                $classMiddlewares = $classMiddlewareAnnotation->getMiddlewares();
            }

            /** @var ResourceMapping $classResourceAnnotation */
            $classResourceAnnotation = $reader->getClassAnnotation($class, ResourceMapping::class);
            if ($classControllerAnnotation) {
                $classPath = $classPrefix . $classResourceAnnotation->getPath();
                $classMethods = $classResourceAnnotation->getMethods();
                $classAllowMethods = $classResourceAnnotation->getAllowMethods();
                $tempClassAnnotations[] = [
                    'method' => $classMethods,
                    'className' => $className,
                    'path' => $classPath,
                    'allowMethods' => $classAllowMethods,
                    'middleware' => $classMiddlewares,
                ];
                $resourceMatch = true;
            }

            $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $item) {
                $action = $item->name;
                if ($resourceMatch && self::checkResourceAction($action, $classAllowMethods)) {
                    continue;
                }
                /** @var Middleware $methodMiddlewareAnnotations */
                $methodMiddlewareAnnotations = $reader->getMethodAnnotation($item, Middleware::class);

                $methodMappingAnnotations = [
                    $reader->getMethodAnnotation($item, RequestMapping::class),
                    $reader->getMethodAnnotation($item, GetMapping::class),
                    $reader->getMethodAnnotation($item, PostMapping::class),
                    $reader->getMethodAnnotation($item, PutMapping::class),
                    $reader->getMethodAnnotation($item, DeleteMapping::class),
                ];

                $methodMiddlewares = $methodMiddlewareAnnotations ? $methodMiddlewareAnnotations->getMiddlewares() : [];
                /** @var \Shayvmo\WebmanAnnotations\Annotations\Mapping $mappingAnnotation */
                foreach ($methodMappingAnnotations as $mappingAnnotation) {
                    if ($mappingAnnotation) {
                        $mappingPaths = $mappingAnnotation->getPath();
                        if (is_string($mappingPaths)) {
                            $mappingPaths = [$mappingPaths];
                        }
                        foreach ($mappingPaths as $mappingPath) {
                            $tempClassAnnotations[] = [
                                'method' => $mappingAnnotation->getMethods(),
                                'path' => $classPrefix . $mappingPath,
                                'className' => $className,
                                'action' => $action,
                                'middleware' => array_merge($classMiddlewares, $methodMiddlewares),
                            ];
                        }
                    }
                }
            }
        }
        return $tempClassAnnotations;
    }

    private static function checkResourceAction(string $action, array $allowActions = []): bool
    {
        $actions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'recovery'];
        if ($allowActions) {
            $actions = array_intersect($actions, $allowActions);
        }
        return in_array($action, $actions, true);
    }
}
