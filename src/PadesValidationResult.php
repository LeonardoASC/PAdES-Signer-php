<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

final readonly class PadesValidationResult
{
    /**
     * @param array<string, bool> $checks
     * @param array<string> $messages
     * @param array<string, array{valid:bool,checks:array<string,bool>,messages:array<string>}> $profiles
     */
    public function __construct(
        public string $profile,
        public bool $valid,
        public array $checks,
        public array $messages = [],
        public array $profiles = []
    ) {}

    public function isProfile(string $profile): bool
    {
        return $this->profile === $profile;
    }

    public function passed(string $check): bool
    {
        return $this->checks[$check] ?? false;
    }

    /**
     * @return array<string>
     */
    public function failedChecks(): array
    {
        return array_keys(array_filter(
            $this->checks,
            static fn (bool $passed): bool => ! $passed
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'profile' => $this->profile,
            'valid' => $this->valid,
            'checks' => $this->checks,
            'messages' => $this->messages,
            'profiles' => $this->profiles,
        ];
    }
}
