# PAdES Core

PAdES Core is a native PHP library for generating and validating PAdES-compatible PDF digital signatures.

It exposes a public PAdES-first API for signing, validating, enriching LTV/LTA
material and producing operational reports, while keeping PDF/CMS/ASN.1 internals
as implementation details.

## Features

- Internal CMS generation for PAdES PDF signatures
- Public PAdES-first signing API
- Public validation API for B-B, B-T, B-LT and B-LTA
- Optional LTV/LTA enrichment helpers
- Configurable trust store
- SigningCertificateV2 support
- Incremental PDF updates
- PDF signature dictionary generation
- ByteRange calculation and validation
- AcroForm and Widget generation
- OpenSSL verification compatibility
- Visible and invisible signatures
- Dedicated final signature page

---

## Current Capabilities

The current implementation includes:

- Incremental PDF signing
- Detached CMS signatures generated internally for PAdES
- SigningCertificateV2 attribute
- PAdES-B-B and PAdES-B-T signing
- PAdES-B-LT and PAdES-B-LTA enrichment primitives
- Public validation result objects and reports
- Public error hierarchy
- IssuerAndSerialNumber generation
- DER ASN.1 encoder
- Structural PDF signature validation
- ByteRange validation
- OpenSSL-compatible CMS verification
- Configurable visible signature widgets
- Basic PDF appearance stream generation
- CI-safe test suite and manual interoperability workflow

---

## Installation

```bash
composer require nihillabs/pades-core
```

This package is framework-agnostic and can be used directly in any Composer PHP
application, including Laravel, Symfony and other frameworks.

---

## Requirements

- PHP 8.4+
- OpenSSL extension enabled

---

## Basic Usage

PAdES-B-B signature:

```php
use NihilLabs\Pades\Credentials\PfxCredential;
use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesSignatureOptions;

$credential = PfxCredential::fromFile(
    path: '/secure/certificate.pfx',
    password: $passwordTypedWhenSigning
);

$result = Pades::signBb(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    credential: $credential,
    options: new PadesSignatureOptions(
        signatureName: 'Signer Name',
        signatureReason: 'Digital signature',
        signatureLocation: 'Internal system',
        signatureContactInfo: 'signer@example.com'
    )
);
```

PAdES-B-T signature with TSA:

```php
use NihilLabs\Pades\Credentials\PfxCredential;
use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesSignatureOptions;

$result = Pades::signBt(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    credential: PfxCredential::fromFile('/secure/certificate.pfx', $passwordTypedWhenSigning),
    timestampProvider: new HttpTimestampClient('https://tsa.example.com/tsr'),
    options: new PadesSignatureOptions(
        signatureName: 'Signer Name',
        signatureReason: 'Digital signature',
        signatureLocation: 'Internal system',
        signatureContactInfo: 'signer@example.com'
    )
);
```

Certificate imported by the user and stored outside the filesystem:

```php
use NihilLabs\Pades\Certificate\PfxCertificateImporter;
use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\Signing\PfxSignatureCredential;

$uploadedPfxContents = file_get_contents($_FILES['certificate']['tmp_name']);

$metadata = (new PfxCertificateImporter())->inspectContents(
    contents: $uploadedPfxContents,
    password: $passwordTypedOnImport
);

// Store $uploadedPfxContents and selected $metadata fields.
// Do not store the certificate password.

$credential = PfxSignatureCredential::fromContents(
    contents: $storedPfxContents,
    password: $passwordTypedWhenSigning
);

Pades::signBb(
    inputPdf: 'document.pdf',
    outputPdf: 'document-signed.pdf',
    credential: $credential,
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

CI-safe suite without local certificate secrets:

```bash
vendor/bin/phpunit -c phpunit.ci.xml --display-skipped --display-warnings
```

Interoperability setup is documented in `docs/CI-INTEROPERABILIDADE.md`.

---

## Current Status

Recommended first production target:

- PAdES-B-B for the main signing flow.
- PAdES-B-T when a real RFC 3161 TSA is configured.

Functional but environment-sensitive:

- PAdES-B-LT depends on real OCSP/CRL evidence and a usable certificate chain.
- PAdES-B-LTA depends on archival timestamps and trust anchors accepted by the target validator.
- Adobe/Reader/browser compatibility should be recorded per release using the manual hash-based tests.

Validated externally in the current project history:

- ITI accepted B-B, B-T and B-LT fixtures generated with a real certificate/TSA.
- DSS recognized the signatures and reported trust-store-dependent outcomes.
- B-LTA with FreeTSA may remain indeterminate in validators that do not trust that TSA.

---

## Roadmap

- Public release hardening
- More test fixtures that do not require local secrets
- Full parser streaming for very large PDFs
- Broader CI matrix and release automation
- HSM, smartcard, KMS and remote-signing provider examples

---

## Important Notes

This project is under active development. Use the public API documented in
`docs/API-USO.md`; avoid depending on internal namespaces such as `Pdf`,
`Internal`, `Crypto\Asn1` and CMS builders.

Production use should define certificate storage, password handling, TSA choice,
trust store, file-size limits and validator targets explicitly. See
`docs/ESCOPO-INTERNO.md` for supported assumptions and current limitations.

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
