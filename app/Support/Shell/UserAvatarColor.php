<?php

namespace App\Support\Shell;

class UserAvatarColor
{
    /**
     * @return array{background: string, text: string}
     */
    public function for(?string $name): array
    {
        $palettes = [
            ['background' => 'bg-sky-100', 'text' => 'text-sky-700'],
            ['background' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
            ['background' => 'bg-amber-100', 'text' => 'text-amber-700'],
            ['background' => 'bg-rose-100', 'text' => 'text-rose-700'],
        ];

        return $palettes[crc32((string) $name) % count($palettes)];
    }
}
