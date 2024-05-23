<?php

declare(strict_types=1);


namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MiddlewareIgnore
{
    public array $middlewares;

    public function __construct(...$value)
    {
        $middlewares = $value[0]['value'] ?? [];
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->middlewares = $middlewares;
    }

    /**
     * @return array|mixed
     */
    public function getMiddlewares()
    {
        return $this->middlewares;
    }
}
