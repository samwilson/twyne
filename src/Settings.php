<?php

namespace App;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class Settings
{

    /** @var SettingRepository */
    private $settingRepository;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $projectDir;

    /** @var mixed[] Keys are settings' names, values are their values. */
    private $data;

    public function __construct(
        SettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
        string $projectDir
    ) {
        $this->settingRepository = $settingRepository;
        $this->entityManager = $entityManager;
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

    public function saveData(array $data)
    {
        foreach ($data as $name => $value) {
            $setting = $this->settingRepository->findOneBy(['name' => $name]);
            if (!$setting) {
                $setting = new Setting();
            }
            $setting->setName($name);
            $setting->setValue($value);
            $this->entityManager->persist($setting);
        }
        $this->entityManager->flush();
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
        return rtrim($this->getData()['data_dir'] ?? $this->projectDir . '/var/app_data', '/') . '/';
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

    public function flickrApiKey(): string
    {
        return $this->getData()['flickr_api_key'] ?? '';
    }

    public function flickrApiSecret(): string
    {
        return $this->getData()['flickr_api_secret'] ?? '';
    }

    public function flickrToken(): string
    {
        return $this->getData()['flickr_token'] ?? '';
    }

    public function flickrTokenSecret(): string
    {
        return $this->getData()['flickr_token_secret'] ?? '';
    }
}
