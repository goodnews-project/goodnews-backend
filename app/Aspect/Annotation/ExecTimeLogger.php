<?php

namespace App\Aspect\Annotation;
use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ExecTimeLogger extends AbstractAnnotation
{
    public function __construct(
        public string $name = 'hyperf',
        public string $group = 'default',
        public bool|array $logParams = false,
    )
    {
        
    }
}
