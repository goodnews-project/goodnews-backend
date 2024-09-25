<?php

namespace App\Service;

use App\Model\Setting;
use Hyperf\Redis\Redis;
use function Hyperf\Support\make;

/**
 * @method static string receive_remote_sensitive() 接收站外敏感内容广播
 * @method static string push_local_sensitive() 对外广播本站敏感内容
 */
class SettingService
{
    const S_SETTINGS_KEY = 's:settings:key';

    public static function getSettings()
    {
        $redis = make(Redis::class);
        if ($settingsJson = $redis->get(self::S_SETTINGS_KEY)) {
            return json_decode($settingsJson, true);
        }
        $settings = Setting::whereNull('settingable_id')->pluck('value','key')->toArray();
        $redis->set(self::S_SETTINGS_KEY, json_encode($settings));
        return $settings;
    }

    public static function __callStatic($name, $arguments)
    {
        $settings = static::getSettings();
        if (!array_key_exists($name, $settings)) {
            return null;
        }

        return $settings[$name];
    }

}