<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

abstract class Mapping
{
    public $path;

    /**
     * @return string | array
     */
    public function getPath()
    {
        return $this->path;
    }

    abstract public function getMethods();
}
