<?php

namespace App;

use App\Entity\Contact;
use App\Entity\Setting;
use App\Entity\User;
use App\Entity\UserGroup;
use App\Repository\ContactRepository;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class Settings
{
    /** @var SettingRepository */
    private $settingRepository;

    /** @var ContactRepository */
    private $contactRepository;

    /** @var int */
    private $mainContactId;

    /** @var Contact|null */
    private $mainContact;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var string */
    private $mailFrom;

    /** @var mixed[] Keys are settings' names, values are their values. */
    private $data;

    public function __construct(
        SettingRepository $settingRepository,
        ContactRepository $contactRepository,
        int $mainContactId,
        EntityManagerInterface $entityManager,
        string $mailFrom
    ) {
        $this->settingRepository = $settingRepository;
        $this->contactRepository = $contactRepository;
        $this->mainContactId = $mainContactId;
        $this->entityManager = $entityManager;
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

    public function apiKey(): string
    {
        return $this->getData()['api_key'] ?? '';
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

    public function getMainContact(): ?Contact
    {
        if ($this->mainContact === null) {
            $this->mainContact = $this->contactRepository->find($this->mainContactId ?? 1);
        }
        return $this->mainContact;
    }

    public function defaultGroup(): int
    {
        return (int)($this->getData()['default_group'] ?? UserGroup::PUBLIC);
    }

    public function getSiteJs(): string
    {
        return $this->getData()['site_js'] ?? '';
    }

    public function getSiteCss(): string
    {
        return $this->getData()['site_css'] ?? '';
    }

    public function overlandKey(): string
    {
        return $this->getData()['overland_key'] ?? '';
    }
}
