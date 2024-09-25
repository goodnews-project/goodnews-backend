<?php

namespace App\Service;
use DOMDocument;


class OpenGraphService
{
    public function parse($html)
    {
        $doc = new DOMDocument();
        $libxml_previous_state = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html,  LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($libxml_previous_state);

        $tags = $doc->getElementsByTagName('meta');
        $metadata = [];
        foreach ($tags as $tag) {
            $metaProperty = ($tag->hasAttribute('property')) ? $tag->getAttribute('property') : $tag->getAttribute('name');
            if (strpos($tag->getAttribute('property'), 'og:') === 0) {
                $key = strtr(substr($metaProperty, 3), '-', '_');
                $value = $this->getMetaValue($tag);
            }
            if (!empty($key)) {
                $metadata[$key] = $value;
            }
        }
        return $metadata;
    }
    
    protected function getMetaValue($tag)
    {
        if (!empty($tag->getAttribute('content'))) {
            $value = $tag->getAttribute('content');
        } elseif (!empty($tag->getAttribute('value'))) {
            $value = $tag->getAttribute('value');
        } else {
            $value = '';
        }

        return $value;
    }
}
