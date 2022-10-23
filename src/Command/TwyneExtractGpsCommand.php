<?php

namespace App\Command;

use App\Entity\Post;
use App\Filesystems;
use App\Repository\PostRepository;
use LongitudeOne\Spatial\PHP\Types\Geometry\Point;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TwyneExtractGpsCommand extends Command
{
    protected static $defaultName = 'twyne:extract-gps';

    /** @var PostRepository */
    private $postRepository;

    /** @var Filesystems */
    private $filesystems;

    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(
        PostRepository $postRepository,
        Filesystems $filesystems,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->postRepository = $postRepository;
        $this->filesystems = $filesystems;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setDescription('Extract GPS locations from uploaded files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $posts = $this->postRepository->findWithoutLocation();
        /** @var Post $post */
        foreach ($posts as $post) {
            $output->write(sprintf('Updating %9s . . . ', 'P' . $post->getId()));
            if (!$post->getFile()) {
                $output->writeln('no file');
                continue;
            }
            $exif = $this->filesystems->readExif($post->getFile());
            if (
                (isset($exif['GPSLongitude']) && $exif['GPSLongitude'])
                || (isset($exif['GPSLatitude']) && $exif['GPSLatitude'])
            ) {
                $post->setLocation(new Point($exif['GPSLongitude'], $exif['GPSLatitude']));
                $this->entityManager->persist($post);
                $this->entityManager->flush();
                $output->writeln(
                    '<info>saved '
                    . $post->getLocation()->getLongitude()
                    . ', ' . $post->getLocation()->getLatitude() . '</info>'
                );
            } else {
                $output->writeln('no Exif data found');
            }
        }
        return Command::SUCCESS;
    }
}
