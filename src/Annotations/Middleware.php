<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class Middleware
{
    public function __construct(...$value)
    {
        echo "Middleware __construct ------------------------\n";
        var_export($value);
        echo " Middleware __construct end ---------------------------\n";
    }
}
