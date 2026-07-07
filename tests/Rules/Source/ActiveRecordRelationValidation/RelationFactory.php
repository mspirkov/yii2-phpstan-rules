<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Tests\Rules\Source\ActiveRecordRelationValidation;

final class RelationFactory
{
    /**
     * @param array<string, string> $link
     */
    public function hasOne(string $class, array $link): void
    {
    }
}
