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

    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    private function getDataStoragePath(File $file): string
    {
        $id = $file->getPost()->getId();
        $bracket = ceil($id / 1000);
        return "/files/$bracket/" . $id . "." . $file->getExtension();
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

    public function tempRoot(): string
    {
        return $this->settings->tempDir();
    }
    
    public function temp(): Filesystem
    {
        $adapter = new Local($this->settings->tempDir());
        return new Filesystem($adapter);
    }

    public function data(): Filesystem
    {
        if ($this->settings->dataStore() === 'aws') {
            $options = [
                'version' => '2006-03-01',
                'credentials' => [
                    'key' => $this->settings->awsKey(),
                    'secret' => $this->settings->awsSecret(),
                ],
                'endpoint' => $this->settings->awsEndpoint(),
                'region' => $this->settings->awsRegion(),
            ];
            $client = new S3Client($options);
            $adapter = new AwsS3Adapter($client, $this->settings->awsBucketName());
        } else {
            $adapter = new Local($this->settings->dataDir());
        }
        return new Filesystem($adapter);
    }
}
