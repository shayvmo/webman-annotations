<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class RequestMapping extends Mapping
{
    public $methods;

    public function __construct(...$value)
    {
        $this->methods = [];
        $this->path = $value[0]['path'] ?? '';
        $tempMethods = $value[0]['methods'] ?? '';
        if ($tempMethods) {
            if (is_string($tempMethods)) {
                $tempMethods = explode(',', $tempMethods);
            }
            array_walk($tempMethods, function (&$item) {
                $item = strtoupper(trim($item));
            });
            $allow_methods = config("plugin.shayvmo.webman-annotations.annotation.allow_methods");
            $allow_methods && $tempMethods = array_filter($tempMethods, function ($item) use ($allow_methods) {
                return in_array($item, $allow_methods, true);
            });
            $this->methods = $tempMethods;
        }
    }

    public function getMethods()
    {
        return $this->methods;
    }
}
