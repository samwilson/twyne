<?php

namespace App;

use App\Repository\SettingRepository;

class Settings
{

    /** @var SettingRepository */
    private $settingRepository;

    /** @var string */
    private $projectDir;

    /** @var mixed[] Keys are settings' names, values are their values. */
    private $data;

    public function __construct(SettingRepository $settingRepository, string $projectDir)
    {
        $this->settingRepository = $settingRepository;
        $this->projectDir = $projectDir;
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

    public function dataStore(): string
    {
        return $this->getData()['data_store'] ?? 'local';
    }

    public function dataDir(): string
    {
        return rtrim($this->getData()['data_dir'], '/') . '/' ?? $this->projectDir . '/var/app_data/';
    }

    public function tempDir(): string
    {
        return rtrim($this->getData()['temp_dir'] ?? $this->projectDir . '/var/app_tmp/', '/') . '/';
    }

    public function awsKey(): string
    {
        return $this->getData()['aws_key'] ?? '';
    }

    public function awsSecret(): string
    {
        return $this->getData()['aws_secret'] ?? '';
    }

    public function awsRegion(): string
    {
        return $this->getData()['aws_region'] ?? '';
    }

    public function awsEndpoint(): string
    {
        return $this->getData()['aws_endpoint'] ?? '';
    }

    public function awsBucketName(): string
    {
        return $this->getData()['aws_bucket_name'] ?? '';
    }
}
