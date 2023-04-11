<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class ResourceMapping extends Mapping
{
    public array $allow_methods = [];

    public function __construct(...$value)
    {
        $this->path = $value[0]['path'] ?? $value[0]['value'] ?? '';
        $tempMethods = $value[0]['allow_methods'] ?? '';
        if ($tempMethods) {
            if (is_string($tempMethods)) {
                $tempMethods = explode(',', strtolower(trim($tempMethods)));
                array_walk($tempMethods, function (&$item) {
                    $item = trim($item);
                });
            }
            $this->allow_methods = $tempMethods;
        }
    }

    /**
     * @return array|string|string[]
     */
    public function getAllowMethods()
    {
        return $this->allow_methods;
    }

    public function getMethods()
    {
        return 'resource';
    }
}
