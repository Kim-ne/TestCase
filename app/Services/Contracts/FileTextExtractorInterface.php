<?php

namespace App\Services\Contracts;

interface FileTextExtractorInterface
{
    public function supports(string $extension): bool;

    public function extract(string $path): string;
}
