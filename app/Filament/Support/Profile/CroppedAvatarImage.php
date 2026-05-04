<?php

declare(strict_types=1);

namespace App\Filament\Support\Profile;

use InvalidArgumentException;

class CroppedAvatarImage
{
    private const MAX_BYTES = 2_097_152;

    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        public readonly string $contents,
        public readonly string $mimeType,
        public readonly string $extension,
        public readonly int $width,
        public readonly int $height,
    ) {}

    public static function fromDataUrl(string $value): self
    {
        if (! preg_match('/^data:(image\/(?:png|jpe?g|webp));base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
            throw new InvalidArgumentException('The avatar must be a cropped image data URL.');
        }

        $contents = base64_decode(str_replace(["\r", "\n"], '', $matches[2]), true);

        if ($contents === false || $contents === '') {
            throw new InvalidArgumentException('The avatar image data is invalid.');
        }

        if (strlen($contents) > self::MAX_BYTES) {
            throw new InvalidArgumentException('The avatar image is too large.');
        }

        $mimeType = (new \finfo(FILEINFO_MIME_TYPE))->buffer($contents);

        if (! is_string($mimeType) || ! array_key_exists($mimeType, self::MIME_EXTENSIONS)) {
            throw new InvalidArgumentException('The avatar image type is not supported.');
        }

        $dimensions = getimagesizefromstring($contents);

        if ($dimensions === false) {
            throw new InvalidArgumentException('The avatar image dimensions could not be read.');
        }

        [$width, $height] = $dimensions;

        if ($width !== $height || $width < 128 || $width > 1024) {
            throw new InvalidArgumentException('The avatar must be a square image between 128px and 1024px.');
        }

        return new self(
            contents: $contents,
            mimeType: $mimeType,
            extension: self::MIME_EXTENSIONS[$mimeType],
            width: $width,
            height: $height,
        );
    }
}
