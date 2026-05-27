<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Validation\PadesBaselineProfile;

final readonly class PadesProfile
{
    public const string B_B = PadesBaselineProfile::B_B;
    public const string B_T = PadesBaselineProfile::B_T;
    public const string B_LT = PadesBaselineProfile::B_LT;
    public const string B_LTA = PadesBaselineProfile::B_LTA;

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::B_B,
            self::B_T,
            self::B_LT,
            self::B_LTA,
        ];
    }
}
