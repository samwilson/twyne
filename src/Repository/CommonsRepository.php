<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\File;
use App\Entity\Post;
use App\Filesystems;
use Exception;
use Mediawiki\Api\ApiUser;
use Mediawiki\Api\FluentRequest;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;

class CommonsRepository
{

    /** @var SyndicationRepository */
    private $syndicationRepository;

    /** @var Filesystems */
    private $filesystems;

    /** @var string */
    private $commonsUrl;

    /** @var string */
    private $depictsProp;

    /** @var string */
    private $commonsUsername;

    /** @var string */
    private $commonsPassword;

    /** @var MediawikiApi */
    private $api;

    public function __construct(
        SyndicationRepository $syndicationRepository,
        Filesystems $filesystems,
        string $commonsUrl,
        string $depictsProp,
        string $commonsUsername,
        string $commonsPassword
    ) {
        $this->syndicationRepository = $syndicationRepository;
        $this->filesystems = $filesystems;
        $this->commonsUrl = $commonsUrl;
        $this->depictsProp = $depictsProp;
        $this->commonsUsername = $commonsUsername;
        $this->commonsPassword = $commonsPassword;
    }

    public function getCommonsUrl(): string
    {
        return $this->commonsUrl;
    }

    /**
     * @param Post $post
     * @param string $title
     * @param string $text
     * @param string $caption
     * @return mixed[] With possible keys: 'url', 'filename', 'warnings'.
     */
    public function upload(Post $post, string $title, string $text, string $caption, array $depicts): array
    {
        $outStream = $this->filesystems->read($post->getFile(), File::SIZE_FULL);
        $tempFilePath = 'commons/' . $post->getId() . '.' . $post->getFile()->getExtension();
        $tempFs = $this->filesystems->temp();
        if (!$tempFs->has($tempFilePath)) {
            $tempFs->writeStream($tempFilePath, $outStream);
        }
        $fullTempPath = $this->filesystems->tempRoot() . $tempFilePath;
        $api = $this->getMediaWikiApi();
        $uploader = new CommonsFileUploader($api);
        $uploadResult = $uploader->uploadWithResult($title, $fullTempPath, $text);
        $tempFs->delete($tempFilePath);

        if ($uploadResult['upload']['result'] !== 'Success') {
            return $uploadResult['upload'];
        }

        // The resulting wiki page name, with 'File:' prefix.
        $filename = $uploadResult['upload']['imageinfo']['canonicaltitle'];
        $wikiUrl = $uploadResult['upload']['imageinfo']['descriptionurl'];

        // Syndication.
        $this->syndicationRepository->addSyndication($post, $wikiUrl, 'Wikimedia Commons');

        // Get info.
        $info = $api->getRequest(FluentRequest::factory()
            ->setAction('query')
            ->setParam('titles', $filename));
        if (!isset($info['query']['pages'])) {
            throw new Exception('Unable to get info about ' . $wikiUrl);
        }
        $pageInfo = array_shift($info['query']['pages']);

        // Caption.
        $mediaId = 'M' . $pageInfo['pageid'];
        $params = [
            'language' => 'en',
            'id' => $mediaId,
            'value' => $caption,
            'token' => $api->getToken(),
        ];
        $api->postRequest(new SimpleRequest('wbsetlabel', $params));

        // Depicts.
        foreach ($depicts as $depict) {
            $params = [
                'entity' => $mediaId,
                'snaktype' => 'value',
                'property' => $this->depictsProp,
                'value' => json_encode(['entity-type' => 'item', 'id' => $depict]),
                'token' => $api->getToken(),
            ];
            $api->postRequest(new SimpleRequest('wbcreateclaim', $params));
        }

        return [
            'filename' => $filename,
            'url' => $wikiUrl,
        ];
    }

    private function getMediaWikiApi(): MediawikiApi
    {
        if ($this->api instanceof MediawikiApi) {
            return $this->api;
        }
        $this->api = MediawikiApi::newFromPage($this->getCommonsUrl());
        $this->api->login(new ApiUser($this->commonsUsername, $this->commonsPassword));
        return $this->api;
    }
}
