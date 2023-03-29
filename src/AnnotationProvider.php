<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations;

use Doctrine\Common\Annotations\AnnotationReader;
use ReflectionClass;
use ReflectionMethod;
use Shayvmo\WebmanAnnotations\Annotations\DeleteMapping;
use Shayvmo\WebmanAnnotations\Annotations\GetMapping;
use Shayvmo\WebmanAnnotations\Annotations\Middleware;
use Shayvmo\WebmanAnnotations\Annotations\PostMapping;
use Shayvmo\WebmanAnnotations\Annotations\PutMapping;
use Shayvmo\WebmanAnnotations\Annotations\RequestMapping;
use Shayvmo\WebmanAnnotations\Annotations\ResourceMapping;
use Shayvmo\WebmanAnnotations\Annotations\RestController;
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
            if (PHP_MAJOR_VERSION >= 8) {
                $tempClassAnnotations[] = self::format8($annotationClass);
            }
            $tempClassAnnotations[] = self::format7(clone $annotationReader, $annotationClass);
        }
        return array_merge(...$tempClassAnnotations);
    }

    private static function format7($reader, $annotationClass)
    {
        $class = new ReflectionClass($annotationClass);
        $resourceMatch = false;
        $classAllowMethods = [];
        $className = $class->name;
        $tempClassAnnotations = [];
        $classPrefix = '';
        /** @var RestController $classControllerAnnotation */
        $classControllerAnnotation = $reader->getClassAnnotation($class, RestController::class);
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
        if ($classResourceAnnotation) {
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

        return $tempClassAnnotations;
    }

    private static function format8($annotationClass)
    {
        $class = new ReflectionClass($annotationClass);
        $resourceMatch = false;
        $classAllowMethods = [];
        $className = $class->name;
        $tempClassAnnotations = [];
        $classPrefix = '';

        /** @var \ReflectionAttribute $classControllerAnnotation */
        $classControllerAnnotations = $class->getAttributes(RestController::class);
        if ($classControllerAnnotations) {
            foreach ($classControllerAnnotations as $classControllerAnnotation) {
                $classControllerAnnotationArgs = $classControllerAnnotation->getArguments();
                $classPrefix = $classControllerAnnotationArgs['path'] ?? current($classControllerAnnotationArgs) ?: '';
            }
        }


        $classMiddlewares = [];
        /** @var \ReflectionAttribute $classMiddlewareAnnotation */
        $classMiddlewareAnnotations = $class->getAttributes(Middleware::class);
        if ($classMiddlewareAnnotations) {
            foreach ($classMiddlewareAnnotations as $classMiddlewareAnnotation) {
                $args = $classMiddlewareAnnotation->getArguments();
                if (is_string($args[0])) {
                    $classMiddlewares[] = [$args[0]];
                } elseif (is_array($args[0])) {
                    $classMiddlewares[] = $args[0];
                }
            }
            $classMiddlewares = array_merge(...$classMiddlewares);
        }

        /** @var \ReflectionAttribute $classResourceAnnotation */
        $classResourceAnnotations = $class->getAttributes(ResourceMapping::class);
        if ($classResourceAnnotations) {
            foreach ($classResourceAnnotations as $classResourceAnnotation) {
                $classResourceAnnotationArgs = $classResourceAnnotation->getArguments();
                $classPath = $classPrefix . ($classResourceAnnotationArgs['path'] ?? $classResourceAnnotationArgs[0] ?? '');
                $classAllowMethods = $classResourceAnnotationArgs['allow_methods'] ?? [];
                $tempClassAnnotations[] = [
                    'method' => 'resource',
                    'className' => $className,
                    'path' => $classPath,
                    'allowMethods' => $classAllowMethods,
                    'middleware' => $classMiddlewares,
                ];
            }
            $resourceMatch = true;
        }

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $item) {
            $action = $item->name;
            if ($resourceMatch && self::checkResourceAction($action, $classAllowMethods)) {
                continue;
            }
            $methodMiddlewares = [];
            $methodMiddlewareAnnotations = $item->getAttributes(Middleware::class);
            if ($methodMiddlewareAnnotations) {
                /** @var \ReflectionAttribute $methodMiddlewareAnnotation */
                foreach ($methodMiddlewareAnnotations as $methodMiddlewareAnnotation) {
                    $args = $methodMiddlewareAnnotation->getArguments();
                    if (is_string($args[0])) {
                        $methodMiddlewares[] = [$args[0]];
                    } elseif (is_array($args[0])) {
                        $methodMiddlewares[] = $args[0];
                    }
                }
                $methodMiddlewares = array_merge(...$methodMiddlewares);
            }

            $methodMappingAnnotations = [
                $item->getAttributes(RequestMapping::class),
                $item->getAttributes(GetMapping::class),
                $item->getAttributes(PostMapping::class),
                $item->getAttributes(PutMapping::class),
                $item->getAttributes(DeleteMapping::class),
            ];

            foreach ($methodMappingAnnotations as $mappingAnnotation) {
                if ($mappingAnnotation) {
                    /** @var \ReflectionAttribute $item */
                    foreach ($mappingAnnotation as $item) {
                        $itemArgs = $item->getArguments();
                        $mappingPaths = $itemArgs['path'] ?? $itemArgs[0] ?? '';
                        if (is_string($mappingPaths)) {
                            $mappingPaths = [$mappingPaths];
                        }
                        if ($item->getName() === RequestMapping::class) {
                            $method = $itemArgs['methods'];
                            if (is_array($method)) {
                                array_walk($method, function (&$m) {
                                    $m = strtoupper($m);
                                });
                            }
                        } else {
                            $method = $item->newInstance()->getMethods();
                        }
                        foreach ($mappingPaths as $mappingPath) {
                            $tempClassAnnotations[] = [
                                'method' => $method,
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
