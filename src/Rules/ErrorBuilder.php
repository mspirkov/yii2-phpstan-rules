<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;

final class ErrorBuilder
{
    /**
     * @param value-of<Identifiers::LIST> $identifier
     */
    public static function build(string $message, string $identifier, ?int $line = null): IdentifierRuleError
    {
        $builder = RuleErrorBuilder::message($message)->identifier($identifier);

        if ($line !== null) {
            $builder = $builder->line($line);
        }

        return $builder->build();
    }
}
