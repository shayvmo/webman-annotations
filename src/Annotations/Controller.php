<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class Controller
{
    public string $prefix;

    public function __construct(...$value)
    {
        $this->prefix = $value[0]['prefix'] ?? $value[0]['value'] ?? '';
    }

    /**
     * @return mixed|string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
}
