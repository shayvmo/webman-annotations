<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class GetMapping extends Mapping
{
    public function __construct(...$value)
    {
        $this->path = $value[0]['value'] ?? '';
    }

    public function getMethods()
    {
        return 'get';
    }
}
