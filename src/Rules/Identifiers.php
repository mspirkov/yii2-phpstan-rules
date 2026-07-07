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
    public const COMPONENT_BEHAVIORS_VALIDATION = self::PREFIX . 'componentBehaviorsValidation';
    public const ACTIVE_RECORD_RELATION_VALIDATION = self::PREFIX . 'activeRecordRelationValidation';
    public const MODEL_RULES_VALIDATION = self::PREFIX . 'modelRulesValidation';

    public const LIST = [
        self::NO_DIRECT_SUPERGLOBALS,
        self::NO_COMPLEX_ACTION_CLASSES,
        self::NO_COMPLEX_CONTROLLER_ACTIONS,
        self::NO_CONTROLLER_ACTION_CALLS_VIA_THIS,
        self::NO_DB_QUERIES_IN_ACTIONS,
        self::NO_DB_QUERIES_IN_CONTROLLERS,
        self::NO_DB_QUERIES_IN_VIEWS,
        self::NO_DYNAMIC_QUERY_WHERE,
        self::NO_FORBIDDEN_YII_APP_PROPERTIES,
        self::NO_YII_APP_PROPERTY_MUTATION,
        self::COMPONENT_BEHAVIORS_VALIDATION,
        self::ACTIVE_RECORD_RELATION_VALIDATION,
        self::MODEL_RULES_VALIDATION,
    ];

    private const PREFIX = 'mspirkovYii2Rules.';
}
