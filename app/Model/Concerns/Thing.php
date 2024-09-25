<?php

declare(strict_types=1);

namespace App\Model\Concerns;

use App\Model\ThingSetting;

trait Thing {
    public static array $jsonFields = [
        ThingSetting::VAR_FILTER_LANGUAGE
    ];

    public function settingMap()
    {
        return $this->thingSetting->each(function ($item) {
            if ($item->value && in_array($item->var, self::$jsonFields)) {
                $item->value = json_decode($item->value, true);
            }
        })->pluck('value', 'var');
    }

    public function thingSetting()
    {
        return $this->morphMany(ThingSetting::class, 'thing');
    }

    public function getDisplayMedia()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_DISPLAY_MEDIA);
    }

    public function getDefaultPrivacy()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_DEFAULT_PRIVACY);
    }

    public function getDefaultSensitive()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_DEFAULT_SENSITIVE);
    }

    public function getShowApplication()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_SHOW_APPLICATION);
    }

    public function getUseBlurhash()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_USE_BLURHASH);
    }

    public function getExpandSpoilers()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_EXPAND_SPOILERS);
    }

    public function getPublishLanguage()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_PUBLISH_LANGUAGE);
    }

    public function getFilterLanguage()
    {
        return $this->getSettingValueByVar(ThingSetting::VAR_FILTER_LANGUAGE);
    }

    public function getSettingValueByVar($var)
    {
        $setting = $this->thingSetting->where('var', $var)->first();
        $value = $setting->value ?? null;
        if ($value && in_array($var, self::$jsonFields)) {
            return json_decode($value, true);
        }
        return $value;
    }
}
