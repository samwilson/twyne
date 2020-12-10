<?php

namespace App\Command;

use App\Entity\Post;
use App\Entity\Setting;
use App\Entity\UserGroup;
use App\Repository\FileRepository;
use App\Repository\PostRepository;
use App\Repository\SyndicationRepository;
use App\Repository\UserGroupRepository;
use App\Settings;
use Exception;
use OAuth\Common\Storage\Memory;
use OAuth\OAuth1\Token\StdOAuth1Token;
use Psr\Log\LoggerInterface;
use Samwilson\PhpFlickr\PhpFlickr;
use Samwilson\PhpFlickr\Util;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TwyneFlickrCommand extends Command
{
    protected static $defaultName = 'twyne:flickr';

    /** @var SymfonyStyle */
    private $io;

    /** @var Settings */
    private $settings;

    /** @var PostRepository */
    private $postRepository;

    /** @var FileRepository */
    private $fileRepository;

    /** @var SyndicationRepository */
    private $syndicationRepository;

    /** @var UserGroupRepository */
    private $userGroupRepository;

    /** @var PhpFlickr */
    private $phpFlickr;

    /** @var HttpClientInterface */
    private $httpClient;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $groupFriends;

    /** @var int */
    private $groupFamily;

    /** @var int */
    private $groupFriendsFamily;

    /** @var int */
    private $groupPrivate;

    /** @var string Force override of photos' owner/author name. */
    private $authorName;

    /** @var mixed[] Runtime cache of user info. */
    private $userInfo = [];

    /** @var bool */
    private $recheckComments;

    public function __construct(
        Settings $settings,
        PostRepository $postRepository,
        FileRepository $fileRepository,
        SyndicationRepository $syndicationRepository,
        UserGroupRepository $userGroupRepository,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->settings = $settings;
        $this->postRepository = $postRepository;
        $this->fileRepository = $fileRepository;
        $this->syndicationRepository = $syndicationRepository;
        $this->userGroupRepository = $userGroupRepository;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Import Flickr photos')
            ->addOption('userid', 'u', InputOption::VALUE_REQUIRED, 'Flickr user ID')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force re-authentication')
            ->addOption('author', 'a', InputOption::VALUE_REQUIRED, 'Author name (to use for the created posts)')
            ->addOption(
                'check-comments',
                null,
                InputOption::VALUE_NONE,
                'Check again for comments on photos that have already been imported.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->phpFlickr = new PhpFlickr($this->settings->flickrApiKey(), $this->settings->flickrApiSecret());

        // Check connection, just to make sure the consumer key is correct. We don't care about the return value;
        // if there's an issue an exception will be thrown.
        $this->logger->debug('Checking Flickr login.');
        $this->phpFlickr->test()->testEcho();

        $hasToken = $this->settings->flickrToken() && $this->settings->flickrTokenSecret();
        $hasForceOpt = $input->hasOption('force') && $input->getOption('force');

        if (!$hasToken || $hasForceOpt) {
            $this->io->block('Authorization required.');
            $url = $this->phpFlickr->getAuthUrl('read');
            $this->io->block('Go to this URL:');
            $this->io->writeln($url);
            // Flickr says, at this point:
            // "You have successfully authorized the application XYZ to use your credentials.
            // You should now type this code into the application:"
            $verifier = $this->io->ask('Past the code here', null, static function ($code) {
                return preg_replace('/[^0-9]/', '', $code);
            });
            $accessToken = $this->phpFlickr->retrieveAccessToken($verifier);
            // Save the access token.
            $this->settings->saveData([
                'flickr_token' => $accessToken->getAccessToken(),
                'flickr_token_secret' => $accessToken->getAccessTokenSecret(),
            ]);
        } else {
            $accessToken = new StdOAuth1Token();
            $accessToken->setAccessToken($this->settings->flickrToken());
            $accessToken->setAccessTokenSecret($this->settings->flickrTokenSecret());
            // A storage object has already been created at this point because we called testEcho above.
            $this->phpFlickr->getOauthTokenStorage()->storeAccessToken('Flickr', $accessToken);
        }

        // Check authorization.
        $userInfo = $this->phpFlickr->test()->login();
        $this->io->success('Logged in as ' . $userInfo['username'] . ' (ID ' . $userInfo['id'] . ')');
        if (!$input->getOption('userid')) {
            $this->logger->debug('Setting userid to ' . $userInfo['id']);
            $input->setOption('userid', $userInfo['id']);
        }

        // Set up user groups.
        $this->groupFriends = $this->userGroupRepository->findOrCreate('Friends')->getId();
        $this->groupFamily = $this->userGroupRepository->findOrCreate('Family')->getId();
        $this->groupFriendsFamily = $this->userGroupRepository->findOrCreate('Friends and family')->getId();
        $this->groupPrivate = $this->userGroupRepository->findOrCreate('Private')->getId();

        // Author.
        $this->authorName = $input->getOption('author');

        // Re-check comments.
        $this->recheckComments = (bool)$input->getOption('check-comments');

        // Main loop.
        $perPage = 500;
        $page = 1;
        $extras = 'description, license, date_upload, date_taken, owner_name, original_format, '
            . 'last_update, geo, tags, machine_tags, media, url_o';
        do {
            $photos = $this->phpFlickr->people()->getPhotos(
                $input->getOption('userid'),
                null,
                null,
                null,
                null,
                null,
                null,
                null,
                $extras,
                $perPage,
                $page
            );
            $this->io->writeln(
                'Page ' . $photos['page'] . ' of ' . $photos['pages']
                . " (" . $photos['total'] . " photos)\n"
            );
            // Import this page's worth of photos.
            foreach ($photos['photo'] as $photoInfo) {
                if (isset($photoInfo['media']) && $photoInfo['media'] === 'video') {
                    // Ignore this photo if it's a video.
                    continue;
                }
                $this->importOnePhoto($photoInfo);
            }
            $page++;
        } while ($page < $photos['pages']);

        return Command::SUCCESS;
    }

    private function getFlicrkUserInfo(string $ownerId)
    {
        if (isset($this->userInfo[$ownerId])) {
            return $this->userInfo[$ownerId];
        }
        $this->logger->debug('Getting info about user ' . $ownerId);
        $info = $this->phpFlickr->people()->getInfo($ownerId);
        if (!isset($info['person'])) {
            throw new Exception('Unable to get info about user: ' . $ownerId);
        }
        $this->userInfo[$ownerId] = $info['person'];
        return $this->userInfo[$ownerId];
    }

    /**
     * @param string[] $photo
     */
    public function importOnePhoto(array $photo)
    {
        // The photo URL is also available in the getInfo call below, but we don't want to call that for every photo.
        $ownerInfo = $this->getFlicrkUserInfo($photo['owner']);
        $photoUrl = 'https://www.flickr.com/photos/' . $ownerInfo['path_alias'] . '/' . $photo['id'];
        $fileUrl = $photo['url_o'];

        // See if we need to import this.
        $syndication = $this->syndicationRepository->findOneBy(['url' => $photoUrl]);
        if ($syndication) {
            $this->io->writeln(
                '      - ' . $photo['id'] . ' already imported as P' . $syndication->getPost()->getId()
                . ' (syndication match)'
            );
            $this->saveComments($syndication->getPost(), $photo['id']);
            return;
        }

        // Download the original.
        $response = $this->httpClient->request('GET', $fileUrl);
        $tmpFile = sys_get_temp_dir() . '/' . uniqid('twyne_flickr');
        file_put_contents($tmpFile, $response->getContent());

        // Check for duplicate.
        $file = $this->fileRepository->findOneBy(['checksum' => sha1_file($tmpFile)]);
        if ($file) {
            $this->io->writeln(
                '      - ' . $photo['id'] . ' already imported as P' . $file->getPost()->getId()
                . ' (checksum match)'
            );
            $this->saveComments($file->getPost(), $photo['id']);
            // Also add a new syndication.
            $this->syndicationRepository->addSyndication($file->getPost(), $photoUrl, 'Flickr');
            return;
        }

        // We also have to query info on each photo, to get the tags and comments.
        $photoInfo = $this->phpFlickr->photos()->getInfo($photo['id']);
        if (!$photoInfo) {
            throw new Exception('Unable to fetch information about Flickr photo ' . $photo['id']);
        }

        // Tags.
        $tags = [];
        foreach ($photoInfo['tags']['tag'] as $tag) {
            if ($tag['machine_tag']) {
                continue;
            }
            if (preg_match('/checksum:(md5|sha1)=.*/i', $tag['raw']) === 1) {
                // Don't import checksum machine tags.
                continue;
            }
            $tags[] = $tag['raw'];
        }
        // Sets (a.k.a. albums).
        $sets = $this->phpFlickr->photos()->getAllContexts($photo['id']);
        foreach ($sets['set'] ?? [] as $set) {
            $tags[] = $set['title'];
        }


        // Approximate dates.
        if (!empty($photoInfo['dates']['takengranularity'])) {
            $tags[] = 'Approximate date';
            switch ($photo['dates']['takengranularity']) {
                case 8:
                    $tags[] = 'circa';
                    $tags[] = 'c. ' . date('Y', strtotime($photo['dates']['taken']));
                    break;
                case 6:
                    $tags[] = 'year';
                    $tags[] = date('Y', strtotime($photo['dates']['taken']));
                    break;
                case 4:
                    $tags[] = 'month';
                    $tags[] = date('F Y', strtotime($photo['dates']['taken']));
                    break;
            }
        }

        // User Group.
        if ($photoInfo['visibility']['ispublic']) {
            $viewGroup = UserGroup::PUBLIC;
        } elseif ($photoInfo['visibility']['isfriend'] && $photoInfo['visibility']['isfamily']) {
            $viewGroup = $this->groupFriendsFamily;
        } elseif ($photoInfo['visibility']['isfriend']) {
            $viewGroup = $this->groupFriends;
        } elseif ($photoInfo['visibility']['isfamily']) {
            $viewGroup = $this->groupFamily;
        } else {
            $viewGroup = $this->groupPrivate;
        }

        $post = new Post();
        $uploadedFile = new UploadedFile($tmpFile, basename($tmpFile));
        $requestParams = [
            'title' => $photo['title'],
            'author' => $this->authorName ?? $photo['ownername'],
            'date' => $photo['datetaken'],
            'body' => $photo['description'],
            'latitude' => $photo['latitude'],
            'longitude' => $photo['longitude'],
            'view_group' => $viewGroup,
            'tags' => join('; ', $tags),
            'new_syndication' => ['url' => $photoUrl, 'label' => 'Flickr'],
        ];
        $this->postRepository->saveFromRequest($post, new Request([], $requestParams), $uploadedFile);
        $this->io->writeln("      - {$photo['id']} imported as P" . $post->getId());
        $this->saveComments($post, $photo['id']);
    }

    private function saveComments(Post $parentPost, string $flickrId): void
    {
        if (!$this->recheckComments) {
            return;
        }
        $this->logger->debug('Checking for comments for ' . $flickrId);
        $comments = $this->phpFlickr->photosComments()->getList($flickrId);
        foreach ($comments['comments']['comment'] ?? [] as $comment) {
            $existingCommentPost = $this->postRepository->findOneBy(['url' => $comment['permalink']]);
            $name = !empty($comment['realname']) ? $comment['realname'] : $comment['authorname'];
            if ($existingCommentPost) {
                $this->io->writeln('        Comment already exists (by ' . $name . ')');
                continue;
            }
            $this->io->writeln('        Saving comment from ' . $name);
            $requestParams = [
                'author' => $name,
                'date' => date('Y-m-d H:i:s', $comment['datecreate']),
                'body' => $comment['_content'],
                'url' => $comment['permalink'],
                'view_group' => $parentPost->getViewGroup(),
                'in_reply_to' => $parentPost->getId(),
            ];
            $this->postRepository->saveFromRequest(new Post(), new Request([], $requestParams));
        }
    }
}
