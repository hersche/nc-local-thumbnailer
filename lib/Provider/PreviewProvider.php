<?php

namespace OCA\Localthumbs\Provider;

use OCP\Preview\IProviderV2;
use OCP\Files\File;
use OCP\Files\FileInfo;
use OCP\IImage;
use OCP\Files\NotFoundException;
use OCP\Files\IAppData;

class PreviewProvider implements IProviderV2 {

    private $appData;

    public function __construct(IAppData $appData) {
        $this->appData = $appData;
    }

    public function getMimeType(): string {
        return '/video\/.*/';
    }

    public function isAvailable(FileInfo $file): bool {
        try {
            $folder = $this->appData->getFolder('thumbs');
            return $folder->fileExists($file->getId() . '.jpg');
        } catch (NotFoundException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
        try {
            $folder = $this->appData->getFolder('thumbs');
            $thumbFile = $folder->getFile($file->getId() . '.jpg');
            $content = $thumbFile->getContent();
            
            $image = new \OCP\Image();
            $image->loadFromData($content);
            
            if ($image->valid()) {
                return $image;
            }
        } catch (\Exception $e) {
            // Log error?
        }
        return null;
    }
}