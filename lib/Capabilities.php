<?php

namespace OCA\Localthumbs;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
    public function getCapabilities() {
        return [
            'localthumbs' => [
                'version' => '1.1.0',
                'features' => [
                    'batch_exists' => true,
                    'sharding' => true,
                    'secret_header' => true,
                ],
            ],
        ];
    }
}
