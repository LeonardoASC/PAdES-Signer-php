# PAdES Core

PAdES Core is a native PHP library for generating and validating PAdES-compatible PDF digital signatures.

> Experimental project focused on low-level PDF digital signature infrastructure in PHP.

## Features

- Internal CMS generation for PAdES PDF signatures
- Public PAdES-first signing API
- SigningCertificateV2 support
- Incremental PDF updates
- PDF signature dictionary generation
- ByteRange calculation and validation
- AcroForm and Widget generation
- OpenSSL verification compatibility
- PAdES-B-B experimental support
- PAdES PDF signature extraction utilities

---

## Current Capabilities

The current implementation includes:

- Incremental PDF signing
- Detached CMS signatures generated internally for PAdES
- SigningCertificateV2 attribute
- IssuerAndSerialNumber generation
- DER ASN.1 encoder
- Structural PDF signature validation
- ByteRange validation
- OpenSSL-compatible CMS verification
- Configurable visible signature widgets
- Basic PDF appearance stream generation

---

## Installation

```bash
composer require nihillabs/pades-core
```

---

## Requirements

- PHP 8.4+
- OpenSSL extension enabled

---

## Basic Usage

```php
use NihilLabs\Pades\Pades;

Pades::sign(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    certificatePath: 'certificate.pfx',
    certificatePassword: getenv('PFX_PASSWORD')
);
```

Certificate imported by the user and stored outside the filesystem:

```php
use NihilLabs\Pades\Certificate\PfxCertificateImporter;
use NihilLabs\Pades\Pades;

$uploadedPfxContents = file_get_contents($_FILES['certificate']['tmp_name']);

$metadata = (new PfxCertificateImporter())->inspectContents(
    contents: $uploadedPfxContents,
    password: $passwordTypedOnImport
);

// Store $uploadedPfxContents and selected $metadata fields.
// Do not store the certificate password.

Pades::signWithPfxContents(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    certificateContents: $storedPfxContents,
    certificatePassword: $passwordTypedWhenSigning
);
```

Visible signature with custom placement:

```php
use NihilLabs\Pades\PadesSigner;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\Signing\PfxSignatureCredential;

(new PadesSigner())->sign(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    credential: new PfxSignatureCredential(
        'certificate.pfx',
        getenv('PFX_PASSWORD')
    ),
    options: new PadesSignatureOptions(
        visibleSignature: true,
        signatureRect: [48, 48, 547, 96],
        signatureName: 'Admin User',
        signatureReason: 'Assinatura digital',
        signatureLocation: 'Prontuario Eletronico',
        signatureContactInfo: 'admin@example.com'
    )
);
```

---

## Running Tests

```bash
vendor/bin/phpunit
```

---

## Current Status

This project is currently experimental but already supports:

- Verifiable CMS signatures
- Advanced signed attributes
- SigningCertificateV2
- OpenSSL CMS verification
- PAdES-B-B oriented structure

Adobe Acrobat recognizes generated signatures as digital signatures.

---

## Roadmap

- Full PAdES-B-B compliance
- PAdES-T timestamp support
- LTV validation
- OCSP integration
- CRL integration
- Multi-signature support
- Certification signatures
- Trust chain validation
- Long-term archival profiles

---

## Important Notes

This project is still under active development and should currently be considered experimental for production environments.

The generated CMS signatures are compatible with OpenSSL verification workflows and are evolving toward broader PAdES interoperability.

For the current internal scope, supported PDF assumptions, unsupported cases and
interoperability matrix, see [docs/ESCOPO-INTERNO.md](docs/ESCOPO-INTERNO.md).
For repository hygiene rules around fixtures, generated files and local signing
material, see [docs/HIGIENE-REPOSITORIO.md](docs/HIGIENE-REPOSITORIO.md).
For minimum usage examples with PFX/P12, PEM, visible signatures and Git Bash
commands, see [docs/USO-MINIMO.md](docs/USO-MINIMO.md).
For public API usage, parameters, return values and integration guidance, see
[docs/API-USO.md](docs/API-USO.md).

---

## License

MIT
