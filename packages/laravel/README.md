# PAdES Laravel

Pacote opcional de integracao Laravel para `nihillabs/pades-core`.

## Instalacao

Em um app Laravel:

```bash
composer require nihillabs/pades-laravel
php artisan vendor:publish --tag=pades-config
```

Para desenvolvimento local via path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../PAdES-Signer-php",
            "options": {
                "symlink": true
            }
        },
        {
            "type": "path",
            "url": "../PAdES-Signer-php/packages/laravel",
            "options": {
                "symlink": true
            }
        }
    ]
}
```

## Uso

```php
use NihilLabs\Pades\Laravel\PadesManager;

$result = app(PadesManager::class)->sign(
    inputPdf: storage_path('app/input.pdf'),
    outputPdf: storage_path('app/output.pdf'),
    certificatePassword: $passwordTypedAtSigning,
    options: app(PadesManager::class)->signatureOptions([
        'signatureName' => 'Nome do assinante',
        'signatureReason' => 'Assinatura digital',
        'signatureLocation' => 'Sistema interno',
        'signatureContactInfo' => 'assinante@example.com',
    ])
);

$result->sha256;
```

## Configuracao

Configure `config/pades.php` ou variaveis:

- `PADES_PFX_PATH`
- `PADES_TSA_URL`
- `PADES_TSA_TIMEOUT`
- `PADES_TRUST_STORE_ENABLED`
- `PADES_TRUST_STORE_DIR`

A senha do PFX nao deve ficar no config. Informe a senha no momento da
assinatura, vinda de input temporario, sessao segura ou vault.
