<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class FakePdfBuilder
{
    public function build(string $contentsPlaceholder): string
    {
        return <<<PDF
%PDF-1.7

1 0 obj
<<
/Type /Sig
/Filter /Adobe.PPKLite
/SubFilter /ETSI.CAdES.detached
/ByteRange [********** ********** ********** **********]
/Contents <{$contentsPlaceholder}>
>>
endobj

%%EOF
PDF;
    }
}
