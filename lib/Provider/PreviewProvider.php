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

    private function getShardFolder(int $fileId) {
        $shard1 = str_pad($fileId % 100, 2, '0', STR_PAD_LEFT);
        $shard2 = str_pad(floor($fileId / 100) % 100, 2, '0', STR_PAD_LEFT);
        
        return $this->appData->getFolder('thumbs')
                    ->getFolder($shard1)
                    ->getFolder($shard2);
    }

    public function getMimeType(): string {
        return '/video\/.*/';
    }

    public function isAvailable(FileInfo $file): bool {
        try {
            $folder = $this->getShardFolder($file->getId());
            return $folder->fileExists($file->getId() . '.jpg');
        } catch (NotFoundException $e) {
            // Check legacy location for backward compatibility
            try {
                return $this->appData->getFolder('thumbs')->fileExists($file->getId() . '.jpg');
            } catch (\Exception $e2) {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getThumbnail(File $file, int $maxX, int $maxY): ?IImage {
        try {
            try {
                $folder = $this->getShardFolder($file->getId());
                $thumbFile = $folder->getFile($file->getId() . '.jpg');
            } catch (NotFoundException $e) {
                // Fallback to legacy location
                $folder = $this->appData->getFolder('thumbs');
                $thumbFile = $folder->getFile($file->getId() . '.jpg');
            }

            $content = $thumbFile->getContent();
            
            $image = new \OCP\Image();
            if (method_exists($image, 'loadFromData')) {
                $image->loadFromData($content);
            } else {
                $image->load($content);
            }
            
            if ($image->valid()) {
                return $image;
            }
        } catch (\Exception $e) {
            // Log error?
        }
        return null;
    }
}
