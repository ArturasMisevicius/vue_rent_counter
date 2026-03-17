<?php

namespace App\Support\Shell;

class UserAvatarColor
{
    /**
     * @return array{background: string, ring: string, text: string}
     */
    public function for(string $name): array
    {
        $palette = [
            [
                'background' => 'bg-amber-100',
                'ring' => 'ring-amber-200/80',
                'text' => 'text-amber-950',
            ],
            [
                'background' => 'bg-emerald-100',
                'ring' => 'ring-emerald-200/80',
                'text' => 'text-emerald-950',
            ],
            [
                'background' => 'bg-sky-100',
                'ring' => 'ring-sky-200/80',
                'text' => 'text-sky-950',
            ],
            [
                'background' => 'bg-rose-100',
                'ring' => 'ring-rose-200/80',
                'text' => 'text-rose-950',
            ],
            [
                'background' => 'bg-violet-100',
                'ring' => 'ring-violet-200/80',
                'text' => 'text-violet-950',
            ],
        ];

        $index = crc32(mb_strtolower(trim($name))) % count($palette);

        return $palette[$index];
    }
}
