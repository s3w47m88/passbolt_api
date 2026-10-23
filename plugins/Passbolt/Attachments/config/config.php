<?php
return [
    'passbolt' => [
        'plugins' => [
            'attachments' => [
                'version' => '0.1.0',
                'enabled' => true,
                // Max ciphertext size accepted per attachment (bytes). The file
                // is AES-GCM encrypted client-side; ciphertext ~= plaintext + 16B tag.
                'maxFileSize' => 26214400, // 25 MB
            ],
        ],
    ],
];
