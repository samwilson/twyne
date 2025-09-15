<?php

namespace App\Command;

use Exception;
use App\Entity\Post;
use App\Filesystems;
use Mediawiki\Api\ApiUser;
use Doctrine\DBAL\Connection;
use Mediawiki\Api\MediawikiApi;
use Mediawiki\Api\SimpleRequest;
use Mediawiki\Api\UsageException;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TwyneExportMediaWikiCommand extends Command
{
    protected static $defaultName = 'twyne:export:mediawiki';

    private Connection $conn;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->conn = $connection;
    }

    protected function configure()
    {
        $this->setDescription('Export Twyne posts and files to wikitext.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $groups = $this->conn->fetchAllKeyValue('select id,name from user_group');
        $outputDir = realpath(dirname(__DIR__, 2) . '/mediawiki');
        $posts = $this->conn->executeQuery(
            'SELECT p.*,
                YEAR(p.date) AS year,
                MONTH(p.date) AS month,
                c.name AS author,
                ST_X(location) AS longitude,
                ST_Y(location) AS latitude,
                GROUP_CONCAT(t.title SEPARATOR "; ") AS tags,
                s_commons.url AS commons_url,
                s_flickr.url AS flickr_url,
                GROUP_CONCAT(DISTINCT s_other.url SEPARATOR "; ") AS other_urls,
                f.id AS file_id,
                f.mime_type
            FROM post p
                JOIN contact c ON c.id=p.author_id
                LEFT JOIN post_tag pt ON pt.post_id = p.id
                LEFT JOIN tag t ON pt.tag_id = t.id
                LEFT JOIN syndication s_commons ON s_commons.post_id = p.id AND s_commons.label LIKE "%commons%"
                LEFT JOIN syndication s_flickr ON s_flickr.post_id = p.id AND s_flickr.label LIKE "%flickr%"
                LEFT JOIN syndication s_other ON s_other.post_id = p.id 
                    AND s_other.label NOT LIKE "%flickr%"
                    AND s_other.label NOT LIKE "%commons%"
                LEFT JOIN file f ON p.id=f.post_id
            -- where f.id is not null
            GROUP BY p.id
            ORDER BY p.date DESC
            -- ORDER BY RAND()
            -- LIMIT 30
            '
        );
        while ($post = $posts->fetchAssociative()) {
            if (empty($post['title'])) {
                $fileTitle = 'P' . $post['id'];
                $postTitle = '';
            } else {
                $fileTitle = preg_replace('/[#<?>\[\]\|\{\} \!\/]/', '_', $post['title']);
                $postTitle = ' | title = ' . $post['title'] . "\n";
            }
            $templateName = !empty($post['file_id']) ? 'photo' : 'post';
            $outputFileBase = $outputDir . '/' . $templateName . ' ' . $groups[$post['view_group_id']]
                . '/' . $post['year'] . '/' . $post['month']
                . '/P' . $post['id'] . '_' . $fileTitle;
            $outputFileWikitext = $outputFileBase . '.txt';
            if (!is_dir(dirname($outputFileWikitext))) {
                mkdir(dirname($outputFileWikitext), 0755, true);
            }
            $coords = $post['latitude'] ? $post['latitude'] . ', ' . $post['longitude'] : '';
            $commons = str_replace(
                '_',
                ' ',
                str_replace('https://commons.wikimedia.org/wiki/File:', '', $post['commons_url'])
            );
            $flickr = str_replace('https://www.flickr.com/photos/freosam/', '', $post['flickr_url']);
            $wikitext = '{{' . $templateName . "\n"
                . $postTitle
                . ($post['author'] == 'Sam Wilson' ? '' : " | author = {$post['author']}\n")
                . " | date = {$post['date']} +0000\n"
                . " | timezone = 0\n"
                . (!empty($post['url']) ? " | url = {$post['url']}\n" : '' )
                . (!empty($coords) ? " | coordinates = $coords\n" : '' )
                . " | keywords = {$post['tags']}\n"
                . (!empty($commons) ? " | commons = $commons\n" : '' )
                . (!empty($flickr) ? " | flickr = $flickr\n" : '' )
                . (!empty($post['other_urls']) ? " | other_urls = {$post['other_urls']}\n" : '' )
                . " | twyne = P{$post['id']}\n"
                . "}}\n"
                . "{$post['body']}\n";
            if ($templateName === 'post') {
                $wikitext .= "\n{{post footer}}\n";
            }

            $output->writeln("Writing $outputFileWikitext");
            file_put_contents($outputFileWikitext, $wikitext);

            // dump($post);

            if ($post['file_id']) {
                $id = $post['id'];
                $filesDir = '/home/sam/data/do-spaces/sw-twyne/files';
                if ($post['mime_type'] === 'image/jpeg') {
                    $ext = 'jpg';
                } elseif ($post['mime_type'] === 'image/png') {
                    $ext = 'png';
                } elseif ($post['mime_type'] === 'image/gif') {
                    $ext = 'gif';
                } else {
                    throw new \Exception('Unknown file type: ' . $post['mime_type']);
                }
                $bracket = ceil($id / 1000);
                $filePath = $filesDir . "/$bracket/" . $id . "." . $ext;
                $fileDestPath = $outputFileBase . '.' . $ext;
                if (!file_exists($fileDestPath)) {
                    $output->writeln("Copying $filePath ---- $fileDestPath");
                    copy($filePath, $fileDestPath);
                }
            }
        }
        return Command::SUCCESS;
    }
}
