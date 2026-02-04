<?php

namespace OCA\Localthumbs\AppInfo;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCA\Localthumbs\Provider\PreviewProvider;
use OCP\IPreview;

class Application extends App implements IBootstrap {
    public function __construct(array $urlParams = []) {
        parent::__construct('localthumbs', $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerCapability(\OCA\Localthumbs\Capabilities::class);
    }

    public function boot(IBootContext $context): void {
        $server = $context->getServerContainer();
        
        // Register for video files
        try {
            $previewManager = $server->get(IPreview::class);
            $previewManager->registerProvider('/^video\/.*/', function() use ($context) {
                return $context->getAppContainer()->query(PreviewProvider::class);
            });
        } catch (\Exception $e) {
            // Log if needed
        }
    }
}