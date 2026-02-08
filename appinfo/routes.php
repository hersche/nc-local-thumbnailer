<?php

return [
    'routes' => [
        [
            'name' => 'thumbnail#upload',
            'url' => '/thumbnail/upload',
            'verb' => 'POST',
        ],
        [
            'name' => 'thumbnail#exists',
            'url' => '/thumbnail/exists',
            'verb' => 'GET',
        ],
        [
            'name' => 'thumbnail#batch_exists',
            'url' => '/thumbnail/batchExists',
            'verb' => 'POST',
        ],
    ],
];
