<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Rules\Identifiers;
use PhpParser\Node\ArrayItem;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;

final class ComponentObjectConfigAnalyzer
{
    private BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer;

    public function __construct(BaseObjectConfigAnalyzer $baseObjectConfigAnalyzer)
    {
        $this->baseObjectConfigAnalyzer = $baseObjectConfigAnalyzer;
    }

    /**
     * @param class-string $className
     * @param array<string, ArrayItem> $options
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    public function validateObjectOptionNames(
        string $className,
        array $options,
        string $objectLabel,
        string $identifier
    ): array {
        return $this->baseObjectConfigAnalyzer->validateObjectOptionNames(
            $className,
            $this->filterComponentConfigOptions($options),
            $objectLabel,
            $identifier
        );
    }

    /**
     * @param class-string $className
     * @param array<string, ArrayItem> $options
     * @param list<string> $typeCheckSkippedOptions
     * @param value-of<Identifiers::LIST> $identifier
     *
     * @return list<IdentifierRuleError>
     */
    public function validateObjectOptionValueTypes(
        string $className,
        array $options,
        Scope $scope,
        string $optionLabel,
        array $typeCheckSkippedOptions,
        string $identifier
    ): array {
        return $this->baseObjectConfigAnalyzer->validateObjectOptionValueTypes(
            $className,
            $this->filterComponentConfigOptions($options),
            $scope,
            $optionLabel,
            $typeCheckSkippedOptions,
            $identifier
        );
    }

    /**
     * @param array<string, ArrayItem> $options
     *
     * @return array<string, ArrayItem>
     */
    private function filterComponentConfigOptions(array $options): array
    {
        $filteredOptions = [];
        foreach ($options as $optionName => $item) {
            if ($this->isEventOrBehaviorConfigKey($optionName)) {
                continue;
            }

            $filteredOptions[$optionName] = $item;
        }

        return $filteredOptions;
    }

    private function isEventOrBehaviorConfigKey(string $optionName): bool
    {
        return strncmp($optionName, 'on ', 3) === 0 || strncmp($optionName, 'as ', 3) === 0;
    }
}
