<?php

namespace App\Service;

class ViewService
{
    public string $content;

    public function __construct()
    {
        $this->content = file_get_contents('public/index.html');
    }
    
    public function render($title, $meta = [])
    {
        $re = '/<title>(.*)<\/title>/m';


        $metaStr = [];
        foreach ($meta as $key => $value) {
            $metaStr[] = "<meta property=\"{$key}\" content=\"{$value}\" />";
        }
        return preg_replace(
            $re,
            "<title>{$title}</title>\r\n" . implode("\r\n", $metaStr),
            $this->content
        );
    }
}
