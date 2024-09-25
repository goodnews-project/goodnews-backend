<?php

namespace App\Nsq;
use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class NsqQueueMessage extends AbstractAnnotation
{
    
}
