<?php

use Hyperf\Context\ApplicationContext;
use Hyperf\HttpServer\Contract\RequestInterface;
use voku\helper\AntiXSS;

function isSpider(){
    return true;
}
function getClientIp(){
    $request = ApplicationContext::getContainer()->get(RequestInterface::class);
    $realIp = $request->getHeader('x-real-ip');
    // https://wiki.swoole.com/#/http_server?id=server 数组的 key 全部为小写，并且与 PHP 的 $_SERVER 数组保持一致
    if($realIp){
        return is_array($realIp) ? $realIp[0] : $realIp;
    }
    
    return "0.0.0.0";
}

function handleStatusContent(string|null $content){
    if (empty($content)) {
        return $content;
    }
    $config = [
        'HTML.Allowed' => 'p[class],strong,em,del,b,i,s,strike,h1,h2,h3,h4,h5,h6,ul,ol,li,br',
        'HTML.ForbiddenElements' => '',
        'CSS.AllowedProperties' => '',
        'AutoFormat.AutoParagraph' => false,
        'AutoFormat.RemoveEmpty' => false,
        'Attr.AllowedClasses' => [
            'h-feed',
            'h-entry',
            'h-cite',
            'h-card',
            'p-author',
            'p-name',
            'p-in-reply-to',
            'p-repost-of',
            'p-comment',
            'u-photo',
            'u-uid',
            'u-url',
            'dt-published',
            'e-content',
            'mention',
            'hashtag',
            'ellipsis',
            'invisible'
        ],
        'Attr.AllowedRel' => [
            'noreferrer',
            'noopener',
            'nofollow'
        ],
        'HTML.TargetBlank' => false,
        'HTML.Nofollow' => true,
        'URI.DefaultScheme' => 'https',
        'URI.DisableExternalResources' => true,
        'URI.DisableResources' => true,
        'URI.AllowedSchemes' => [
            'http' => true,
            'https' => true,
        ],
    ];
    $htmlPurifier = new HTMLPurifier($config);
    return $htmlPurifier->purify($content);
    
}
function removeXss(string $content){
    $antiXSS = new AntiXSS();
    return $antiXSS->xss_clean($content);
}

function isLocalAp($url)
{
    if (empty($url)) {
        return false;
    }

    $localDomains = explode(',', \Hyperf\Support\env('LOCAL_DOMAIN', ''));
    foreach ($localDomains as $localDomain) {
        if (str_contains(parse_url($url, PHP_URL_HOST), $localDomain)) {
            return true;
        }
    }
    return false;
}

function toProxyUrl($url, $remoteUrl)
{
    if (isLocalAp($url) && $remoteUrl) {
        return getApHostUrl().'/proxy?url='.$remoteUrl;
    }
    return $url;
}

if (!function_exists('getApHostUrl')) {
    function getApHostUrl ()
    {
        return 'https://'.\Hyperf\Support\env('AP_HOST');
    }
}