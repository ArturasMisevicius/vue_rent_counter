<?php

if (! class_exists('Imagick')) {
    class Imagick
    {
        public function __construct() {}

        public function setBackgroundColor(string $color): bool
        {
            return true;
        }

        public function setResolution(float $xResolution, float $yResolution): bool
        {
            return true;
        }

        public function readImageBlob(string $image): bool
        {
            return true;
        }

        public function setImageFormat(string $format): bool
        {
            return true;
        }

        public function addImage(self $image): bool
        {
            return true;
        }

        public function resetIterator(): bool
        {
            return true;
        }

        public function getImagesBlob(): string
        {
            return '';
        }
    }
}
