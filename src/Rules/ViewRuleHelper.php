<?php

declare(strict_types=1);

namespace TomasVotruba\Bladestan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Registry;
use PHPStan\Rules\RuleError;
use PHPStan\ShouldNotHappenException;
use TomasVotruba\Bladestan\Compiler\BladeToPHPCompiler;
use TomasVotruba\Bladestan\ErrorReporting\Blade\TemplateErrorsFactory;
use TomasVotruba\Bladestan\TemplateCompiler\PHPStan\FileAnalyserProvider;
use TomasVotruba\Bladestan\TemplateCompiler\TypeAnalyzer\TemplateVariableTypesResolver;
use TomasVotruba\Bladestan\TemplateCompiler\ValueObject\RenderTemplateWithParameters;

use function array_merge;
use function file_get_contents;
use function file_put_contents;
use function md5;
use function preg_match;
use function sys_get_temp_dir;

final class ViewRuleHelper
{
    private Registry $registry;

    private const ERRORS_TO_IGNORE = [
        'Call to function unset\(\) contains undefined variable \$loop\.',
        'Variable \$loop in PHPDoc tag @var does not exist\.',
        'Anonymous function has an unused use \$[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*\.',
    ];

    public function __construct(
        private TemplateVariableTypesResolver $templateVariableTypesResolver,
        private FileAnalyserProvider $fileAnalyserProvider,
        private TemplateErrorsFactory $templateErrorsFactory,
        private BladeToPHPCompiler $bladeToPhpCompiler,
    ) {
    }

    /**
     * @param RenderTemplateWithParameters[] $renderTemplatesWithParameters
     *
     * @return RuleError[]
     *
     * @throws ShouldNotHappenException
     */
    public function processNode(Node $node, Scope $scope, array $renderTemplatesWithParameters): array
    {
        $ruleErrors = [];
        foreach ($renderTemplatesWithParameters as $renderTemplateWithParameter) {
            $variablesAndTypes = $this->templateVariableTypesResolver->resolveArray(
                $renderTemplateWithParameter->getParametersArray(),
                $scope
            );

            $currentRuleErrors = $this->processTemplateFilePath(
                $renderTemplateWithParameter->getTemplateFilePath(),
                $variablesAndTypes,
                $scope,
                $node->getLine()
            );

            $ruleErrors = array_merge($ruleErrors, $currentRuleErrors);
        }

        return $ruleErrors;
    }

    /**
     * @param \TomasVotruba\Bladestan\TemplateCompiler\ValueObject\VariableAndType[] $variablesAndTypes
     *
     * @return RuleError[]
     *
     * @throws ShouldNotHappenException
     */
    private function processTemplateFilePath(
        string $templateFilePath,
        array $variablesAndTypes,
        Scope $scope,
        int $phpLine
    ): array {
        $fileContents = file_get_contents($templateFilePath);

        if ($fileContents === false) {
            return [];
        }

        $phpFileContentsWithLineMap = $this->bladeToPhpCompiler->compileContent($templateFilePath, $fileContents, $variablesAndTypes);

        $phpFileContents = $phpFileContentsWithLineMap->getPhpFileContents();

        $tmpFilePath = sys_get_temp_dir() . '/' . md5($scope->getFile()) . '-blade-compiled.php';
        file_put_contents($tmpFilePath, $phpFileContents);

        /** @var FileAnalyser $fileAnalyser */
        $fileAnalyser = $this->fileAnalyserProvider->provide();

        $collectorsRegistry = new \PHPStan\Collectors\Registry([]);

        $fileAnalyserResult = $fileAnalyser->analyseFile(
            $tmpFilePath,
            [],
            $this->registry,
            $collectorsRegistry,
            null
        );

        $ruleErrors = $fileAnalyserResult->getErrors();

        foreach ($ruleErrors as $key => $ruleError) {
            foreach (self::ERRORS_TO_IGNORE as $item) {
                if (! preg_match('#' . $item . '#', $ruleError->getMessage())) {
                    continue;
                }

                unset($ruleErrors[$key]);
            }
        }

        return $this->templateErrorsFactory->createErrors(
            $ruleErrors,
            $phpLine,
            $scope->getFile(),
            $phpFileContentsWithLineMap,
        );
    }

    public function setRegistry(Registry $registry): void
    {
        $this->registry = $registry;
    }
}
