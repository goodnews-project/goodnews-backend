<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Util;

use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;

/**
 * @method static void info($message, array $context = [])
 * @method static void error($message, array $context = [])
 * @method static void alert($message, array $context = [])
 * @method static void debug($message, array $context = [])
 * @method static void warning($message, array $context = [])
 * @method static void emergency($message, array $context = [])
 * @method static void notice($message, array $context = [])
 * @method static void critical($message, array $context = [])
 */
class Log
{
    protected static array $hiddenFields = [
        'public_key', 'private_key', 'password', 'publicKeyPem'
    ];
    public static function __callStatic(string $name, array $arguments)
    {
//        if (!empty($arguments[1])) {
//            $arguments[1] = self::filterSensitiveData($arguments[1]);
//        }
        return self::get()->{$name}(...$arguments);
    }

    public static function get(string $name = 'app')
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name);
    }

    public static function filterSensitiveData(array $arr): array
    {
        $filteredArr = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $filteredArr[$key] = self::filterSensitiveData($value);
            } elseif (!in_array($key, self::$hiddenFields)) {
                $filteredArr[$key] = $value;
            }
        }

        return $filteredArr;
    }
}
