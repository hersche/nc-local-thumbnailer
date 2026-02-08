<?php
namespace OCA\Localthumbs\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\Files\IRootFolder;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\IUserSession;

class ThumbnailController extends Controller {
    private $rootFolder;
    private $appData;
    private $userSession;
    private $config;

    public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, IAppData $appData, IUserSession $userSession, \OCP\IConfig $config) {
        parent::__construct($AppName, $request);
        $this->rootFolder = $rootFolder;
        $this->appData = $appData;
        $this->userSession = $userSession;
        $this->config = $config;
    }

    /**
     * Checks if the request has the correct secret header.
     * This is a simple security measure for the internal API.
     */
    private function checkSecret() {
        $secret = $this->config->getAppValue('localthumbs', 'api_secret', '');
        if ($secret === '') {
            return true; // If no secret is set, allow (backward compatibility/simplicity)
        }
        $providedSecret = $this->request->getHeader('X-Localthumbs-Secret');
        return $providedSecret === $secret;
    }

    private function getShardFolder(int $fileId) {
        $shard1 = str_pad($fileId % 100, 2, '0', STR_PAD_LEFT);
        $shard2 = str_pad(floor($fileId / 100) % 100, 2, '0', STR_PAD_LEFT);
        
        try {
            $base = $this->appData->getFolder('thumbs');
        } catch (NotFoundException $e) {
            $base = $this->appData->newFolder('thumbs');
        }

        try {
            $f1 = $base->getFolder($shard1);
        } catch (NotFoundException $e) {
            $f1 = $base->newFolder($shard1);
        }

        try {
            $f2 = $f1->getFolder($shard2);
        } catch (NotFoundException $e) {
            $f2 = $f1->newFolder($shard2);
        }

        return $f2;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function upload(string $path) {
        if (!$this->checkSecret()) {
            return ['status' => 'error', 'message' => 'Invalid secret'];
        }

        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return ['status' => 'error', 'message' => 'Not authenticated'];
            }
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            // Clean path
            $path = ltrim($path, '/');
            
            if (!$userFolder->nodeExists($path)) {
                 return ['status' => 'error', 'message' => 'Node does not exist: ' . $path];
            }
            
            $node = $userFolder->get($path);
            $fileId = $node->getId();

            $uploadedFile = $this->request->getUploadedFile('thumbnail');
            if (!$uploadedFile || !isset($uploadedFile['tmp_name'])) {
                return ['status' => 'error', 'message' => 'No file uploaded or invalid upload'];
            }

            $folder = $this->getShardFolder($fileId);
            $fileName = $fileId . '.jpg';

            // Use streaming for better memory efficiency
            $handle = @fopen($uploadedFile['tmp_name'], 'r');
            if ($handle === false) {
                 return ['status' => 'error', 'message' => 'Failed to open uploaded file: ' . $uploadedFile['tmp_name']];
            }

            try {
                try {
                    $file = $folder->getFile($fileName);
                    $file->putContent($handle);
                } catch (NotFoundException $e) {
                    $folder->newFile($fileName, $handle);
                }
            } finally {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }

            return ['status' => 'success', 'fileId' => $fileId];

        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function exists(string $path) {
        if (!$this->checkSecret()) {
            return ['exists' => false];
        }

         try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return ['exists' => false];
            }
            $userId = $user->getUID();

            $userFolder = $this->rootFolder->getUserFolder($userId);
            $path = ltrim($path, '/');
            
             if (!$userFolder->nodeExists($path)) {
                 return ['exists' => false];
            }
            
            $node = $userFolder->get($path);
            $fileId = $node->getId();
            
            try {
                $folder = $this->getShardFolder($fileId);
                $exists = $folder->fileExists($fileId . '.jpg');
                return ['exists' => $exists];
            } catch (NotFoundException $e) {
                return ['exists' => false];
            }
         } catch (\Exception $e) {
             return ['exists' => false];
         }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function batchExists(array $paths) {
        if (!$this->checkSecret()) {
            return ['status' => 'error', 'message' => 'Invalid secret'];
        }

        try {
            $user = $this->userSession->getUser();
            if (!$user) {
                return ['status' => 'error', 'message' => 'Not authenticated'];
            }
            $userId = $user->getUID();
            $userFolder = $this->rootFolder->getUserFolder($userId);
            
            $results = [];
            foreach ($paths as $path) {
                $path = ltrim($path, '/');
                if (!$userFolder->nodeExists($path)) {
                    $results[$path] = false;
                    continue;
                }
                
                $node = $userFolder->get($path);
                $fileId = $node->getId();
                
                try {
                    $shard1 = str_pad($fileId % 100, 2, '0', STR_PAD_LEFT);
                    $shard2 = str_pad(floor($fileId / 100) % 100, 2, '0', STR_PAD_LEFT);
                    
                    $exists = false;
                    try {
                        $folder = $this->appData->getFolder('thumbs')
                                    ->getFolder($shard1)
                                    ->getFolder($shard2);
                        $exists = $folder->fileExists($fileId . '.jpg');
                    } catch (NotFoundException $e) {
                        $exists = false;
                    }
                    $results[$path] = $exists;
                } catch (\Exception $e) {
                    $results[$path] = false;
                }
            }
            
            return ['status' => 'success', 'results' => $results];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
