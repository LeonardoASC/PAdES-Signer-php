# PAdES Core

PAdES Core is a PHP library for generating and validating PAdES-compatible PDF digital signatures.

> Experimental project focused on native PDF digital signature infrastructure in PHP.

## Features

- PFX certificate loading
- CMS / PKCS#7 detached signatures
- Incremental PDF updates
- PDF signature object generation
- ByteRange calculation and validation
- AcroForm and Widget generation
- CMS extraction utilities
- OpenSSL integration helpers

## Installation

```bash
composer require nihillabs/pades-core

Requirements
PHP 8.4+
OpenSSL extension enabled
Basic Usage
use NihilLabs\Pades\Pdf\RealPdfSigner;

$signer = new RealPdfSigner();

$signer->sign(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    certificatePath: 'certificate.pfx',
    certificatePassword: '123456'
);

Current Status

Current implementation includes:

PDF incremental update support
Signature field generation
Detached CMS signatures
Structural PDF signature validation
ByteRange validation

The project is still under active development and should be considered experimental.

Tests
vendor/bin/phpunit
Roadmap
PAdES-B compliance improvements
Timestamp support (PAdES-T)
LTV validation
Visible signatures
Multi-signature support
Certification signatures
CRL and OCSP validation
License

MIT