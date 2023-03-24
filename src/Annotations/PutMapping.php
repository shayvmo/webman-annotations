<?php

declare(strict_types=1);

namespace Shayvmo\WebmanAnnotations\Annotations;

/**
 * @Annotation
 */
class PutMapping
{
    public function __construct(...$value)
    {
        echo "PutMapping __construct ------------------------\n";
        var_export($value);
        echo " PutMapping __construct end ---------------------------\n";
//        $formattedValue = $this->formatParams($value);
//        $this->path    = $formattedValue["path"];
//        if (isset($formattedValue['methods'])) {
//            if (is_string($formattedValue['methods'])) {
//                // Explode a string to a array
//                $this->methods = explode(',', mb_strtoupper(str_replace(' ', '', $formattedValue['methods'])  , 'UTF-8'));
//            } else {
//                $methods = [];
//                foreach ($formattedValue['methods'] as $method) {
//                    $methods[] = mb_strtoupper(str_replace(' ', '', $method) , 'UTF-8');
//                }
//                $this->methods = $methods;
//            }
//        }
    }

    /**
     * @return array
     * @datetime 2022/7/4 13:50
     * @author zhulianyou
     */
    public function setMethods(): array
    {
        $normalMethods = [];
        foreach ($this->methods as $method)
        {
            if(in_array($method , $this->normal))
            {
                $normalMethods[] = $method;
            }
        }
        return $normalMethods;
    }
}
