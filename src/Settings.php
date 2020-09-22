<?php

namespace App;

use App\Repository\SettingRepository;

class Settings
{

    /** @var SettingRepository */
    private $settingRepository;

    /** @var mixed[] Keys are settings' names, values are their values. */
    private $data;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    private function getData(): array
    {
        if (is_array($this->data)) {
            return $this->data;
        }
        $this->data = [];
        foreach ($this->settingRepository->findAll() as $setting) {
            $this->data[$setting->getName()] = $setting->getValue();
        }
        return $this->data;
    }

    public function siteName(): string
    {
        return $this->getData()['site_name'] ?? 'A Twyne Site';
    }
}
