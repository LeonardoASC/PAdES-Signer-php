# PAdES Core

PAdES Core is a native PHP library for generating and validating PAdES-compatible PDF digital signatures.

> Experimental project focused on low-level PDF digital signature infrastructure in PHP.

## Features

- Internal CMS generation for PAdES PDF signatures
- Advanced CMS builder with ASN.1 DER encoding
- SigningCertificateV2 support
- Incremental PDF updates
- PDF signature dictionary generation
- ByteRange calculation and validation
- AcroForm and Widget generation
- CMS extraction utilities
- OpenSSL verification compatibility
- PAdES-B-B experimental support
- PAdES PDF signature extraction utilities

---

## Current Capabilities

The current implementation includes:

- Incremental PDF signing
- Detached CMS signatures
- CMS SignedData generation
- SignerInfo generation
- SignedAttributes generation
- SigningCertificateV2 attribute
- IssuerAndSerialNumber generation
- DER ASN.1 encoder
- Structural PDF signature validation
- ByteRange validation
- OpenSSL-compatible CMS verification

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
use NihilLabs\Pades\Pdf\RealPdfSigner;

$signer = new RealPdfSigner();

$signer->sign(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    certificatePath: 'certificate.pfx',
    certificatePassword: '123456'
);
```

---

## Running Tests

```bash
vendor/bin/phpunit
```

---

## OpenSSL CMS Verification

Extract the CMS signature and signed content:

```bash
php extract.php
php extract-signed-data.php
```

Verify CMS integrity:

```bash
openssl cms -verify -binary -inform DER \
  -in tests/Output/signature.der \
  -content tests/Output/signed-data.bin \
  -noverify \
  -out tests/Output/verified-output.bin
```

Expected output:

```txt
CMS Verification successful
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
- Visible signatures
- Multi-signature support
- Certification signatures
- Trust chain validation
- Long-term archival profiles

---

## Important Notes

This project is still under active development and should currently be considered experimental for production environments.

The generated CMS signatures are compatible with OpenSSL verification workflows and are evolving toward broader PAdES interoperability.

---

## License

MIT
