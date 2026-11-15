<?php

namespace Config;

use CodeIgniter\Shield\Config\AuthJWT as ShieldAuthJWT;

class AuthJWT extends ShieldAuthJWT
{
    /**
     * --------------------------------------------------------------------
     * Name of Authenticator Header
     * --------------------------------------------------------------------
     * The name of Header that the Authorization token should be found.
     */
    public string $authenticatorHeader = 'Authorization';

    /**
     * --------------------------------------------------------------------
     * The Default Payload Items
     * --------------------------------------------------------------------
     * All JWTs will have these claims in the payload.
     */
    public array $defaultClaims = [
        'iss' => 'http://localhost:8080/',
    ];

    /**
     * --------------------------------------------------------------------
     * The Keys
     * --------------------------------------------------------------------
     * Keyset configuration.
     */
    public array $keys = [];

    public function __construct()
    {
        parent::__construct();

        // Ensure JWT issuer matches dynamic base url of the application
        $this->defaultClaims['iss'] = base_url();

        // Secret wajib berasal dari environment/.env; jangan simpan fallback di kode.
        $secretKey = env('JWT_SECRET');
        $secretKey = is_string($secretKey) ? trim($secretKey) : $secretKey;

        if (! is_string($secretKey) || strlen($secretKey) < 32) {
            throw new \RuntimeException('JWT_SECRET wajib diisi di .env dengan minimal 32 karakter.');
        }

        $this->keys = [
            'default' => [
                [
                    'kid'    => '',
                    'alg'    => 'HS256',
                    'secret' => $secretKey,
                ],
            ],
        ];
    }
}
