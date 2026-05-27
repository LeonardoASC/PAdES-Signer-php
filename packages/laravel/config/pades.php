<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Certificate
    |--------------------------------------------------------------------------
    |
    | Keep the PFX/P12 file outside public directories. The password should be
    | provided by the request/session/vault at signing time, not persisted here.
    |
    */
    'certificate' => [
        'path' => env('PADES_PFX_PATH'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signature Defaults
    |--------------------------------------------------------------------------
    */
    'signature' => [
        'visible' => (bool) env('PADES_VISIBLE_SIGNATURE', true),
        'append_signature_page' => (bool) env('PADES_APPEND_SIGNATURE_PAGE', true),
        'rect' => [48, 48, 547, 96],
        'flags' => 132,
        'type' => 'approval',
        'certification_permission' => 2,
        'field_lock_action' => 'Include',
        'hash_algorithm' => 'sha256',
        'signature_algorithm' => 'rsa',
        'minimum_hash_algorithm' => 'sha256',
        'include_signing_time' => false,
        'page_media_box' => [0, 0, 595, 842],
        'page_rect' => [48, 120, 547, 700],
    ],

    /*
    |--------------------------------------------------------------------------
    | TSA
    |--------------------------------------------------------------------------
    */
    'tsa' => [
        'url' => env('PADES_TSA_URL'),
        'timeout' => (int) env('PADES_TSA_TIMEOUT', 15),
        'validate_with_trust_store' => (bool) env('PADES_TSA_VALIDATE_TRUST_STORE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Trust Store
    |--------------------------------------------------------------------------
    |
    | Use PEM trust anchors. You can provide a directory, explicit file paths,
    | inline PEM strings, or combine all three.
    |
    */
    'trust_store' => [
        'enabled' => (bool) env('PADES_TRUST_STORE_ENABLED', false),
        'directory' => env('PADES_TRUST_STORE_DIR'),
        'paths' => [],
        'certificates' => [],
    ],
];
