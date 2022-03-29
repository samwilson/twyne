<?php

namespace App;

use App\Entity\File;
use Aws\S3\S3Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Process\Process;

class Filesystems
{

    /** @var string */
    private $dataStore;

    /** @var string */
    private $dataDir;

    /** @var string */
    private $tempDir;

    /** @var string */
    private $awsRegion;

    /** @var string */
    private $awsEndpoint;

    /** @var string */
    private $awsBucket;

    /** @var string */
    private $awsKey;

    /** @var string */
    private $awsSecret;

    public function __construct(
        string $projectDir,
        string $dataStore,
        string $dataDir,
        string $tempDir,
        string $awsRegion,
        string $awsEndpoint,
        string $awsBucket,
        string $awsKey,
        string $awsSecret
    ) {
        $this->dataStore = $dataStore;
        $this->dataDir = rtrim(!empty($dataDir) ? $dataDir : $projectDir . '/var/app_data', '/') . '/';
        $this->tempDir = rtrim(!empty($tempDir) ? $tempDir : $projectDir . '/var/app_tmp/', '/') . '/';
        $this->awsRegion = $awsRegion;
        $this->awsEndpoint = $awsEndpoint;
        $this->awsBucket = $awsBucket;
        $this->awsKey = $awsKey;
        $this->awsSecret = $awsSecret;
    }

    private function getDataStoragePath(File $file): string
    {
        $id = $file->getPost()->getId();
        $bracket = ceil($id / 1000);
        return "/files/$bracket/" . $id . "." . $file->getExtension();
    }

    /**
     * Download the full-sized original of a file to local temp and return its path.
     *
     * @return string Full local filesystem path the to file.
     */
    public function getLocalTempFilepath(File $file): string
    {
        $outStream = $this->read($file, File::SIZE_FULL);
        $tempFilePath = 'local_tmp/' . $file->getPost()->getId() . '.' . $file->getExtension();
        $tempFs = $this->temp();
        if (!$tempFs->has($tempFilePath)) {
            $tempFs->writeStream($tempFilePath, $outStream);
        }
        return $this->tempRoot() . $tempFilePath;
    }

    public function write(Filesystem $fs, File $file, string $filePath)
    {
        $storagePath = $this->getDataStoragePath($file);
        $stream = fopen($filePath, 'r');
        if ($fs->has($storagePath)) {
            $fs->updateStream($storagePath, $stream);
        } else {
            $fs->writeStream($storagePath, $stream);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    /**
     * Delete a file from both filesystems.
     * @param File $file
     * @return bool
     */
    public function remove(File $file): bool
    {
        // Delete actual file.
        $storagePath = $this->getDataStoragePath($file);
        $dataDeleted = true;
        if ($this->data()->has($storagePath)) {
            $dataDeleted = $this->data()->delete($storagePath);
        }
        // Delete temp versions.
        $tempDeleted = $this->temp()->deleteDir('/files/' . $file->getPost()->getId());
        return $dataDeleted && $tempDeleted;
    }

    /**
     * Get a stream of a file, resizing it if requested (in which case it'll always be a JPEG).
     * @param File $file The file to fetch.
     * @param string $size One of the File::SIZE_* constants.
     * @return resource
     */
    public function read(File $file, string $size)
    {
        $id = $file->getPost()->getId();

        $filepathTempResized = '/files/' . $id . '/' . $size . '.jpg';
        $filepathTempOrig = '/files/' . $id . '/' . File::SIZE_FULL . '.' . $file->getExtension();
        $filepathData = $this->getDataStoragePath($file);

        $dataFs = $this->data();
        $tempFs = $this->temp();

        if ($size === File::SIZE_FULL) {
            // If the original is being requested, return it.
            $outStream = $dataFs->readStream($filepathData);
        } elseif ($tempFs->has($filepathTempResized)) {
            // If the requested size and type already exists in the temp FS, return it.
            $outStream = $tempFs->readStream($filepathTempResized);
        } else {
            // Otherwise, generate the requested size and type.
            if (!$dataFs->has($filepathData)) {
                throw new NotFoundHttpException("Unable to find $filepathData in the data store.");
            }
            if (!$tempFs->has($filepathTempOrig)) {
                $tempFs->writeStream($filepathTempOrig, $dataFs->readStream($filepathData));
            }
            $pixelArea = $size === File::SIZE_DISPLAY ? 200000 : 30000;
            $from = $this->tempRoot() . $filepathTempOrig;
            if ($file->getExtension() === 'pdf') {
                $from .= '[0]';
            }
            $to = $this->tempRoot() . $filepathTempResized;
            $convert = new Process(['convert', '-resize', "$pixelArea@>", $from, $to]);
            $convert->mustRun();
            $outStream = $tempFs->readStream($filepathTempResized);
            // Remove the original as it's no longer needed in the temp FS.
            $tempFs->delete($filepathTempOrig);
        }

        return $outStream;
    }

    public function readExif(File $file): array
    {
        if (substr($file->getMimeType(), 0, strlen('image')) !== 'image') {
            // Not an image.
            return [];
        }

        $dataFs = $this->data();
        $tempFs = $this->temp();
        $dataName = $this->getDataStoragePath($file);
        $tmpName = 'exif_file_temp.' . $file->getExtension();

        if (!$dataFs->has($dataName)) {
            // Should never happen, but isn't uncommon in dev environments.
            return [];
        }

        // Write original file to the temp directory.
        if (!$tempFs->has($tmpName)) {
            $tempFs->writeStream($tmpName, $dataFs->readStream($dataName));
        }

        // Extract Exif data.
        $cmd = new Process(['exiftool', '-json', '-n', $this->tempRoot() . $tmpName]);
        $cmd->mustRun();
        $data = json_decode($cmd->getOutput(), true);
        // We only request one file's metadata, so it's always going to be the first one.
        $exif = isset($data[0]) ? $data[0] : [];

        // Clean up and return;
        $tempFs->delete($tmpName);
        return $exif;
    }

    /**
     * Filesystem path of the root of the temporary directory.
     * Always has a trailing slash.
     * @return string
     */
    public function tempRoot(): string
    {
        return $this->tempDir;
    }

    public function temp(): Filesystem
    {
        $adapter = new Local($this->tempRoot());
        return new Filesystem($adapter);
    }

    public function data(): Filesystem
    {
        if ($this->dataStore === 'aws') {
            $options = [
                'version' => '2006-03-01',
                'credentials' => [
                    'key' => $this->awsKey,
                    'secret' => $this->awsSecret,
                ],
                'endpoint' => $this->awsEndpoint,
                'region' => $this->awsRegion,
            ];
            $client = new S3Client($options);
            $adapter = new AwsS3Adapter($client, $this->awsBucket);
        } else {
            $adapter = new Local($this->dataDir);
        }
        return new Filesystem($adapter);
    }
}
