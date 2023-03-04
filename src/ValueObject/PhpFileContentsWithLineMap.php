<?php

declare(strict_types=1);

namespace TomasVotruba\Bladestan\ValueObject;

/** @see \TomasVotruba\Bladestan\TemplateCompiler\ValueObject\PhpFileContentsWithLineMap */
final class PhpFileContentsWithLineMap
{
    /**
     * @param array<int, array<string, int>> $phpToTemplateLines
     */
    public function __construct(
        private string $phpFileContents,
        private array $phpToTemplateLines
    ) {
    }

    public function getPhpFileContents(): string
    {
        return $this->phpFileContents;
    }

    /**
     * @return array<int, array<string, int>>
     */
    public function getPhpToTemplateLines(): array
    {
        return $this->phpToTemplateLines;
    }
}
