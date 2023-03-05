<?php

declare(strict_types=1);

namespace TomasVotruba\Bladestan\TemplateCompiler\ValueObject;

use Webmozart\Assert\Assert;

final class PhpFileContentsWithLineMap
{
    /**
     * @param array<int, int> $phpToTemplateLines
     */
    public function __construct(
        private readonly string $phpFileContents,
        private readonly array $phpToTemplateLines
    ) {
        Assert::allInteger(array_keys($phpToTemplateLines));
        Assert::allInteger($phpToTemplateLines);
    }

    public function getPhpFileContents(): string
    {
        return $this->phpFileContents;
    }

    /**
     * @return array<int, int>
     */
    public function getPhpToTemplateLines(): array
    {
        return $this->phpToTemplateLines;
    }
}
