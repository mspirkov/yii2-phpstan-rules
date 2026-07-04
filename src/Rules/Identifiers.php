<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Rules;

final class Identifiers
{
    public const NO_DIRECT_SUPERGLOBALS = self::PREFIX . 'noDirectSuperglobals';
    public const NO_COMPLEX_ACTION_CLASSES = self::PREFIX . 'noComplexActionClasses';
    public const NO_COMPLEX_CONTROLLER_ACTIONS = self::PREFIX . 'noComplexControllerActions';
    public const NO_CONTROLLER_ACTION_CALLS_VIA_THIS = self::PREFIX . 'noControllerActionCallsViaThis';
    public const NO_DB_QUERIES_IN_ACTIONS = self::PREFIX . 'noDbQueriesInActions';
    public const NO_DB_QUERIES_IN_CONTROLLERS = self::PREFIX . 'noDbQueriesInControllers';
    public const NO_DB_QUERIES_IN_VIEWS = self::PREFIX . 'noDbQueriesInViews';
    public const NO_DYNAMIC_QUERY_WHERE = self::PREFIX . 'noDynamicQueryWhere';
    public const NO_FORBIDDEN_YII_APP_PROPERTIES = self::PREFIX . 'noForbiddenYiiAppProperties';
    public const NO_YII_APP_PROPERTY_MUTATION = self::PREFIX . 'noYiiAppPropertyMutation';
    public const MODEL_RULES_VALIDATION = self::PREFIX . 'modelRulesValidation';

    private const PREFIX = 'mspirkovYii2Rules.';
}
