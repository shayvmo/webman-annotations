<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class Controller
{
    public function __construct(...$value)
    {
        echo "Controller __construct ------------------------\n";
        var_export($value);
        echo " Controller __construct end ---------------------------\n";
    }
}
