<?php

declare (strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class ResourceMapping extends Mapping
{
    public array $allow_methods = [];

    public function __construct(...$value)
    {
        $this->path = $value[0]['path'] ?? $value[0]['value'] ?? '';
        $tempMethods = trim($value[0]['methods'] ?? '');
        if ($tempMethods) {
            if (is_string($tempMethods)) {
                $tempMethods = explode(',', strtolower($tempMethods));
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
