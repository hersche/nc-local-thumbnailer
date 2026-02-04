<?php

namespace OCA\Localthumbs;

use OCP\Capabilities\ICapability;

class Capabilities implements ICapability {
    public function getCapabilities() {
        return [
            'localthumbs' => [
                'version' => '1.0.0',
            ],
        ];
    }
}
