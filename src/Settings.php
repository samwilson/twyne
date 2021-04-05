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

    /** @var string */
    private $mailFrom;

    /** @var mixed[] Keys are settings' names, values are their values. */
    private $data;

    public function __construct(
        SettingRepository $settingRepository,
        EntityManagerInterface $entityManager,
        string $projectDir,
        string $mailFrom
    ) {
        $this->settingRepository = $settingRepository;
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
        $this->mailFrom = $mailFrom;
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
        $data['user_registrations'] = isset($data['user_registrations']);
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

    /**
     * Whether users can register new accounts. Site admins are always able to create new users.
     */
    public function userRegistrations(): bool
    {
        return $this->getData()['user_registrations'] ?? true;
    }

    /**
     * Get the email address to send mail from.
     */
    public function getMailFrom()
    {
        return $this->mailFrom;
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

    public function apiKey(): string
    {
        return $this->getData()['api_key'] ?? '';
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
