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

    public function __construct($AppName, IRequest $request, IRootFolder $rootFolder, IAppData $appData, IUserSession $userSession) {
        parent::__construct($AppName, $request);
        $this->rootFolder = $rootFolder;
        $this->appData = $appData;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function upload(string $path) {
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
            if (!$uploadedFile) {
                return ['status' => 'error', 'message' => 'No file uploaded'];
            }

            try {
                $folder = $this->appData->getFolder('thumbs');
            } catch (NotFoundException $e) {
                $folder = $this->appData->newFolder('thumbs');
            }

            $content = file_get_contents($uploadedFile['tmp_name']);
            
            try {
                $file = $folder->getFile($fileId . '.jpg');
                $file->putContent($content);
            } catch (NotFoundException $e) {
                $folder->newFile($fileId . '.jpg', $content);
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
                $folder = $this->appData->getFolder('thumbs');
                $exists = $folder->fileExists($fileId . '.jpg');
                return ['exists' => $exists];
            } catch (NotFoundException $e) {
                return ['exists' => false];
            }
         } catch (\Exception $e) {
             return ['exists' => false];
         }
    }
}
