<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class ResourceMapping
{
    public function __construct(...$value)
    {
        echo "ResourceMapping __construct ------------------------\n";
        var_export($value);
        echo " ResourceMapping __construct end ---------------------------\n";
    }
}
