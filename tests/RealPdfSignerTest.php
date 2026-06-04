<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Tests\Support\TestSignatureCredential;
use NihilLabs\Pades\Tests\Support\TestSignerProvider;
use PHPUnit\Framework\TestCase;

final class RealPdfSignerTest extends TestCase
{
    public function test_it_accepts_a_real_pdf_and_creates_output(): void
    {
        $input = __DIR__ . '/Output/minimal.pdf';
        $output = __DIR__ . '/Output/minimal-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        $this->signPdf($input, $output);

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertStringContainsString('%PDF-', $content);
        $this->assertStringContainsString('xref', $content);
        $this->assertStringContainsString('trailer', $content);
        $this->assertStringContainsString('/Prev ', $content);

        $this->assertStringContainsString('/Type /Sig', $content);
        $this->assertStringContainsString('/Filter /Adobe.PPKLite', $content);
        $this->assertStringContainsString('/SubFilter /ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/Contents <', $content);
        $this->assertStringContainsString('/ByteRange [0 ', $content);
        $this->assertStringContainsString('/Extensions <<', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
        $this->assertStringNotContainsString('/Prop_Build', $content);
    }

    public function test_it_reserves_space_for_large_timestamped_cms_signatures(): void
    {
        $input = __DIR__ . '/Output/minimal-large-placeholder.pdf';
        $output = __DIR__ . '/Output/minimal-large-placeholder-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        $this->signPdf($input, $output);

        $content = file_get_contents($output);

        $this->assertNotFalse($content);

        preg_match(
            '/\/Contents\s*<([0-9A-F]+)>/s',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches[1] ?? null);
        $this->assertGreaterThanOrEqual(
            24000,
            strlen($matches[1])
        );
    }

    public function test_it_fills_signature_placeholders_when_certificate_is_provided(): void
    {
        $input = __DIR__ . '/Output/minimal.pdf';
        $output = __DIR__ . '/Output/minimal-real-signed.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword()
        );

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertStringContainsString('/Type /Sig', $content);
        $this->assertStringContainsString('/SubFilter /ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/ByteRange [0 ', $content);
        $this->assertStringNotContainsString('/ByteRange [**********', $content);
        $this->assertStringContainsString('/Contents <3082', $content);
        $this->assertStringContainsString('/Subtype /Widget', $content);
        $this->assertStringContainsString('/FT /Sig', $content);
        $this->assertStringContainsString('/Fields [', $content);
        $this->assertStringContainsString('/AcroForm ', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
        $this->assertStringNotContainsString('/Prop_Build', $content);
        $this->assertStringContainsString('/Annots [', $content);
        $this->assertStringContainsString('/Subtype /Widget', $content);
        $this->assertStringContainsString('/P ', $content);
        $this->assertStringContainsString('/M (D:', $content);

        $this->assertStringContainsString('/Name (PAdES Core)', $content);

        $this->assertStringContainsString(
            '/Reason (Document signed digitally)',
            $content
        );
    }

    public function test_it_generates_visible_signature_field_when_requested(): void
    {
        $input = __DIR__ . '/Output/minimal-visible-input.pdf';
        $output = __DIR__ . '/Output/minimal-visible-output.pdf';

        if (! is_dir(dirname($input))) {
            mkdir(dirname($input), 0777, true);
        }

        (new MinimalPdfGenerator())->generate($input);

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@adm.com'
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Rect [ 48 48 547 96 ]', $content);
        $this->assertStringContainsString('/F 132', $content);
        $this->assertStringContainsString('/AP <<', $content);
        $this->assertStringContainsString('/Type /XObject', $content);
        $this->assertStringContainsString('/Subtype /Form', $content);
        $this->assertStringContainsString('/BBox [0 0 499 48]', $content);
        $this->assertStringContainsString('/Name (Admin User)', $content);
        $this->assertStringContainsString(
            '/Reason (Assinatura digital de documento assistencial)',
            $content
        );
        $this->assertStringContainsString(
            '/Location (Prontuario Eletronico MPTO)',
            $content
        );
        $this->assertStringContainsString('/ContactInfo (admin@adm.com)', $content);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $content);
        $this->assertStringContainsString('/ESIC <<', $content);
    }

    public function test_it_can_append_a_dedicated_page_for_visible_signature(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-visible-page-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-visible-page-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/2\s+0\s+obj\s*<<.*\/Kids\s*\[3\s+0\s+R\s+\d+\s+0\s+R\].*\/Count\s+2.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/\/Type\s+\/Page\s+\/Parent\s+2\s+0\s+R\s+\/MediaBox\s+\[\s+0\s+0\s+595\s+842\s+\].*\/Annots\s+\[\d+\s+0\s+R\]/s', $content);
        $this->assertStringContainsString('/Rect [ 48 120 547 700 ]', $content);
        $this->assertStringContainsString('/BBox [0 0 499 580]', $content);
    }

    public function test_it_appends_signature_page_to_five_page_pdf_as_sixth_page(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-five-pages-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-five-pages-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R 4 0 R 5 0 R 6 0 R 7 0 R] /Count 5 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            4 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            5 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            6 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            7 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/2\s+0\s+obj\s*<<.*\/Kids\s*\[3\s+0\s+R\s+4\s+0\s+R\s+5\s+0\s+R\s+6\s+0\s+R\s+7\s+0\s+R\s+\d+\s+0\s+R\].*\/Count\s+6.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/\/Type\s+\/Page\s+\/Parent\s+2\s+0\s+R\s+\/MediaBox\s+\[\s+0\s+0\s+595\s+842\s+\]\s+\/Annots\s+\[\d+\s+0\s+R\]/s', $content);
    }

    public function test_it_appends_signature_page_to_nested_page_tree(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-nested-pages-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-nested-pages-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [10 0 R] /Count 5 >>",
            3 => "<< /Type /Page /Parent 10 0 R /MediaBox [0 0 612 792] >>",
            4 => "<< /Type /Page /Parent 10 0 R /MediaBox [0 0 612 792] >>",
            5 => "<< /Type /Page /Parent 10 0 R /MediaBox [0 0 612 792] >>",
            6 => "<< /Type /Page /Parent 10 0 R /MediaBox [0 0 612 792] >>",
            7 => "<< /Type /Page /Parent 10 0 R /MediaBox [0 0 612 792] >>",
            10 => "<< /Type /Pages /Parent 2 0 R /Kids [3 0 R 4 0 R 5 0 R 6 0 R 7 0 R] /Count 5 /Resources 20 0 R >>",
            20 => "<< /ProcSet [/PDF] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/2\s+0\s+obj\s*<<.*\/Kids\s*\[10\s+0\s+R\].*\/Count\s+6.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/10\s+0\s+obj\s*<<.*\/Kids\s*\[3\s+0\s+R\s+4\s+0\s+R\s+5\s+0\s+R\s+6\s+0\s+R\s+7\s+0\s+R\s+\d+\s+0\s+R\].*\/Count\s+6.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/\/Type\s+\/Page\s+\/Parent\s+10\s+0\s+R\s+\/MediaBox\s+\[\s+0\s+0\s+595\s+842\s+\]\s+\/Annots\s+\[\d+\s+0\s+R\]\s+>>/s', $content);
        $this->assertDoesNotMatchRegularExpression('/\/Type\s+\/Page\s+\/Parent\s+10\s+0\s+R(?:(?!endobj).)*\/Resources\s*<</s', $content);
    }

    public function test_it_appends_signature_page_when_kids_array_is_indirect(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-indirect-kids-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-indirect-kids-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids 8 0 R /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            8 => "[3 0 R]",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/2\s+0\s+obj\s*<<.*\/Kids\s+8\s+0\s+R.*\/Count\s+2.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/8\s+0\s+obj\s*\[3\s+0\s+R\s+\d+\s+0\s+R\]\s*endobj/s', $content);
    }

    public function test_it_uses_configured_signature_page_size_and_large_rect(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-custom-page-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-custom-page-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true,
            signaturePageMediaBox: [0, 0, 612, 792],
            signaturePageRect: [72, 100, 540, 300]
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/MediaBox [ 0 0 612 792 ]', $content);
        $this->assertStringContainsString('/Rect [ 72 100 540 300 ]', $content);
        $this->assertStringContainsString('/BBox [0 0 468 200]', $content);
    }

    public function test_it_appends_signature_page_to_already_signed_pdf(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'pades-first-signature-');
        $second = tempnam(sys_get_temp_dir(), 'pades-second-signature-');

        $this->assertIsString($first);
        $this->assertIsString($second);

        $input = tempnam(sys_get_temp_dir(), 'pades-already-signed-input-');
        $this->assertIsString($input);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf($input, $first);

        $this->signPdf(
            inputPdf: $first,
            outputPdf: $second,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($second);

        $this->assertNotFalse($content);
        $this->assertSame(2, preg_match_all('/\/Type\s+\/Sig\b/', $content));
        $this->assertStringContainsString('/Prev ', $content);
        $this->assertStringContainsString('/Rect [ 48 120 547 700 ]', $content);
    }

    public function test_it_appends_signature_page_with_existing_acroform(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-existing-acroform-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-existing-acroform-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
            5 => "<< /Fields [] /SigFlags 3 >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/5\s+0\s+obj\s*<<.*\/Fields\s*\[\d+\s+0\s+R\].*\/SigFlags\s+3.*>>\s*endobj/s', $content);
        $this->assertMatchesRegularExpression('/\/Type\s+\/Page\s+\/Parent\s+2\s+0\s+R\s+\/MediaBox\s+\[\s+0\s+0\s+595\s+842\s+\]\s+\/Annots\s+\[\d+\s+0\s+R\]/s', $content);
    }

    public function test_it_signs_existing_empty_named_signature_field(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-empty-field-');
        $output = tempnam(sys_get_temp_dir(), 'pades-filled-field-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdfWithEmptySignatureField());

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            signatureFieldName: 'Approval'
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertSame(2, preg_match_all('/\/Subtype\s*\/Widget\b/', $content));
        $this->assertStringContainsString('/T (Approval)', $content);
        $this->assertMatchesRegularExpression('/4\s+0\s+obj\s*<<.*\/V\s+6\s+0\s+R.*>>\s*endobj/s', $content);
        $this->assertDoesNotMatchRegularExpression('/7\s+0\s+obj\s*<<.*\/Subtype\s*\/Widget\b/s', $content);
    }

    public function test_it_respects_seed_values_and_lock_dictionary_on_existing_field(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-seed-field-');
        $output = tempnam(sys_get_temp_dir(), 'pades-seed-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdfWithHierarchicalSeededSignatureField());

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            signatureFieldName: 'Section.Approval',
            signatureReason: 'Approved',
            algorithmPolicy: new SignatureAlgorithmPolicy(
                hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA256
            )
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertMatchesRegularExpression('/6\s+0\s+obj\s*<<.*\/V\s+7\s+0\s+R.*>>\s*endobj/s', $content);
        $this->assertStringContainsString('/TransformMethod /FieldMDP', $content);
        $this->assertStringContainsString('/Action /Include', $content);
        $this->assertStringContainsString('/Fields [(Amount) (Section.Approval)]', $content);
    }

    public function test_it_rejects_seed_value_reason_not_allowed_by_field(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-seed-reject-');
        $output = tempnam(sys_get_temp_dir(), 'pades-seed-reject-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdfWithHierarchicalSeededSignatureField());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Seed Value Dictionary nao permite o motivo de assinatura configurado.');

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            signatureFieldName: 'Section.Approval',
            signatureReason: 'Rejected'
        );
    }

    public function test_it_generates_approval_signature_by_default(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-approval-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-approval-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf($input, $output);

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('/Perms <<', $content);
        $this->assertStringNotContainsString('/TransformMethod /DocMDP', $content);
        $this->assertStringNotContainsString('/Reference [', $content);
    }

    public function test_it_generates_certification_signature_with_doc_mdp_permissions(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-cert-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-cert-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            signatureType: 'certification',
            certificationPermission: 1
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Perms <<', $content);
        $this->assertMatchesRegularExpression('/\/DocMDP\s+\d+\s+0\s+R\b/', $content);
        $this->assertStringContainsString('/Reference [', $content);
        $this->assertStringContainsString('/TransformMethod /DocMDP', $content);
        $this->assertStringContainsString('/TransformParams <<', $content);
        $this->assertStringContainsString('/P 1', $content);
    }

    public function test_it_generates_field_mdp_lock_for_selected_fields(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-lock-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-lock-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            lockedFieldNames: ['Amount', 'Approval'],
            fieldLockAction: 'Include'
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/TransformMethod /FieldMDP', $content);
        $this->assertStringContainsString('/Action /Include', $content);
        $this->assertStringContainsString('/Fields [(Amount) (Approval)]', $content);
    }

    public function test_it_signs_pdf_with_configured_sha512_algorithm(): void
    {
        $input = tempnam(sys_get_temp_dir(), 'pades-sha512-input-');
        $output = tempnam(sys_get_temp_dir(), 'pades-sha512-output-');

        $this->assertIsString($input);
        $this->assertIsString($output);

        file_put_contents($input, $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>",
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword(),
            algorithmPolicy: new SignatureAlgorithmPolicy(
                hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA512
            )
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringNotContainsString('/ByteRange [**********', $content);
        $this->assertStringContainsString('608648016503040203', strtoupper($content));
        $this->assertStringContainsString('2A864886F70D01010D', strtoupper($content));
    }

    public function test_real_pdf_signer_generates_pades_b_b_ready_cms(): void
    {
        $input = __DIR__ . '/Output/pades-bb-input.pdf';

        $output = __DIR__ . '/Output/pades-bb-output.pdf';

        (new \NihilLabs\Pades\Pdf\MinimalPdfGenerator())
            ->generate($input);

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword()
        );

        $pdf = file_get_contents($output);

        $this->assertNotFalse($pdf);

        $cms = (new \NihilLabs\Pades\Pdf\PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $inspection = (new \NihilLabs\Pades\Validation\PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }

    public function test_visible_signature_appearance_uses_rendered_text_without_pdf_fonts(): void
    {
        $input = __DIR__ . '/Output/visible-appearance-input.pdf';
        $output = __DIR__ . '/Output/visible-appearance-output.pdf';

        file_put_contents($input, $this->buildPdf([
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>',
        ]));

        $this->signPdf(
            inputPdf: $input,
            outputPdf: $output,
            visibleSignature: true,
            signatureName: 'Admin User',
            signatureReason: 'Assinatura digital de documento assistencial',
            signatureLocation: 'Prontuario Eletronico MPTO',
            signatureContactInfo: 'admin@example.com',
            appendSignaturePage: true
        );

        $content = file_get_contents($output);

        $this->assertNotFalse($content);
        $this->assertStringContainsString('/Subtype /Form', $content);
        $this->assertStringNotContainsString('/BaseFont', $content);
        $this->assertStringNotContainsString('/FontFile', $content);
        $this->assertGreaterThan(100, substr_count($content, ' re f'));
    }

    public function test_signature_page_supports_common_page_sizes_landscape_and_rotated_sources(): void
    {
        $cases = [
            'a4' => [
                'sourcePage' => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>',
                'signaturePageMediaBox' => [0, 0, 595, 842],
                'expectedMediaBox' => '/MediaBox [ 0 0 595 842 ]',
            ],
            'letter' => [
                'sourcePage' => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>',
                'signaturePageMediaBox' => [0, 0, 612, 792],
                'expectedMediaBox' => '/MediaBox [ 0 0 612 792 ]',
            ],
            'landscape' => [
                'sourcePage' => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] >>',
                'signaturePageMediaBox' => [0, 0, 842, 595],
                'expectedMediaBox' => '/MediaBox [ 0 0 842 595 ]',
            ],
            'rotated' => [
                'sourcePage' => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Rotate 90 >>',
                'signaturePageMediaBox' => [0, 0, 612, 792],
                'expectedMediaBox' => '/MediaBox [ 0 0 612 792 ]',
                'expectedSourceMarker' => '/Rotate 90',
            ],
        ];

        foreach ($cases as $name => $case) {
            $input = __DIR__ . "/Output/signature-page-{$name}-input.pdf";
            $output = __DIR__ . "/Output/signature-page-{$name}-output.pdf";

            file_put_contents($input, $this->buildPdf([
                1 => '<< /Type /Catalog /Pages 2 0 R >>',
                2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
                3 => $case['sourcePage'],
            ]));

            $this->signPdf(
                inputPdf: $input,
                outputPdf: $output,
                visibleSignature: true,
                appendSignaturePage: true,
                signaturePageMediaBox: $case['signaturePageMediaBox']
            );

            $content = file_get_contents($output);

            $this->assertNotFalse($content);
            $this->assertStringContainsString($case['expectedMediaBox'], $content);
            $this->assertStringContainsString('/Count 2', $content);

            if (isset($case['expectedSourceMarker'])) {
                $this->assertStringContainsString($case['expectedSourceMarker'], $content);
            }
        }
    }

    private function signPdf(mixed ...$arguments): void
    {
        if (
            ! array_key_exists('signatureCredential', $arguments)
            && ! array_key_exists('certificatePath', $arguments)
        ) {
            $arguments['signatureCredential'] = new TestSignatureCredential();
            $arguments['signerProvider'] = new TestSignerProvider();
        }

        (new RealPdfSigner())->sign(...$arguments);
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            self::markTestSkipped(
                'Configure PADES_INTEROP_PFX_PASSWORD para rodar testes com certificate.pfx local.'
            );
        }

        return $password;
    }

    private function buildPdfWithEmptySignatureField(): string
    {
        return $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /Annots [4 0 R] /MediaBox [0 0 612 792] >>",
            4 => "<< /Type /Annot /Subtype /Widget /FT /Sig /Rect [0 0 0 0] /T (Approval) /F 4 /P 3 0 R >>",
            5 => "<< /Fields [4 0 R] /SigFlags 3 >>",
        ]);
    }

    private function buildPdfWithHierarchicalSeededSignatureField(): string
    {
        return $this->buildPdf([
            1 => "<< /Type /Catalog /Pages 2 0 R /AcroForm 5 0 R >>",
            2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
            3 => "<< /Type /Page /Parent 2 0 R /Annots [6 0 R] /MediaBox [0 0 612 792] >>",
            4 => "<< /FT /Sig /T (Section) /Kids [6 0 R] /SV << /Filter [/Adobe.PPKLite] /SubFilter [/ETSI.CAdES.detached] /DigestMethod [/SHA256] /Reasons [(Approved)] >> /Lock << /Action /Include /Fields [(Amount) (Section.Approval)] >> >>",
            5 => "<< /Fields [4 0 R] /SigFlags 3 >>",
            6 => "<< /Type /Annot /Subtype /Widget /Parent 4 0 R /Rect [0 0 0 0] /T (Approval) /F 4 /P 3 0 R >>",
        ]);
    }

    /**
     * @param array<int, string> $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.7\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($number = 1; $number < $size; $number++) {
            $pdf .= isset($offsets[$number])
                ? sprintf("%010d 00000 n \n", $offsets[$number])
                : "0000000000 65535 f \n";
        }

        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
