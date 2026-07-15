<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii2 PHPStan rules</h1>
</p>

A set of PHPStan rules for Yii2 projects that I put together for my own day-to-day work. Yii2 leans heavily on loosely-typed config arrays and magic properties/methods that PHPStan can't see through on its own, and on conventions — like keeping business logic and database access out of controllers and views — that are easy to drift from without anyone noticing. These rules catch both: they validate Yii2-specific config and structure statically, and they enforce the architectural boundaries I try to keep in a codebase. In my experience they help keep a Yii2 codebase a bit cleaner and more maintainable, but they're just my opinions turned into checks, not a universal standard — use what's useful, ignore or disable the rest.

[![PHP](https://img.shields.io/badge/%3E%3D7.4-7A86B8.svg?style=for-the-badge&logo=php&logoColor=white&label=PHP)](https://www.php.net/releases/7_4_0.php)
[![Yii 2.0.x](https://img.shields.io/badge/%3E%3D2.0.53-247BA0.svg?style=for-the-badge&logo=yii&logoColor=white&label=Yii)](https://github.com/yiisoft/yii2/tree/2.0.53)
[![Tests](https://img.shields.io/github/actions/workflow/status/mspirkov/yii2-phpstan-rules/ci.yml?branch=main&style=for-the-badge&logo=github&label=Tests)](https://github.com/mspirkov/yii2-phpstan-rules/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/mspirkov/yii2-phpstan-rules/ci.yml?branch=main&style=for-the-badge&logo=github&label=PHPStan)](https://github.com/mspirkov/yii2-phpstan-rules/actions/workflows/ci.yml)
[![Coverage](https://img.shields.io/codecov/c/github/mspirkov/yii2-phpstan-rules.svg?branch=main&style=for-the-badge&logo=codecov&logoColor=white&label=Coverage)](https://codecov.io/github/mspirkov/yii2-phpstan-rules)
![PHPStan Level Max](https://img.shields.io/badge/Max-7A86B8.svg?style=for-the-badge&label=PHPStan%20Level)

## What's inside

| Rule                                                                    | Catches                                                                                                                                      |
| ----------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------- |
| [`activeFormFieldValidation`](#active-form-field-validation)            | `ActiveForm::field()` calls targeting an attribute that is missing, read-only, or write-only on the given model                              |
| [`activeRecordRelationValidation`](#active-record-relations-validation) | Invalid `hasOne()` / `hasMany()` link properties that do not exist on the current or related ActiveRecord model                              |
| [`componentBehaviorsValidation`](#component-behaviors-validation)       | Malformed or invalid `behaviors()` in `yii\base\Component` — unknown behavior classes, bad config keys, and bad option types                 |
| [`createObjectValidation`](#yiicreateobject-validation)                 | `Yii::createObject()` config arrays missing `class`/`__class`, bad config keys, and bad option types                                         |
| [`modelAttributeLabelsValidation`](#model-attribute-labels-validation)  | `attributeLabels()` entries in `yii\base\Model` that target attributes that don't exist, or use an empty attribute name                      |
| [`modelRulesValidation`](#model-validation-rules-validation)            | Malformed or invalid `rules()` in `yii\base\Model` — unknown validators, missing required options, bad regexes, unknown attributes, and more |
| [`widgetPropertiesValidation`](#widget-properties-validation)           | Unknown or mistyped option keys and bad option types in `Widget::begin()` / `Widget::widget()` config arrays                                 |
| [`noComplexActionClasses`](#complexity-limits)                          | Standalone `yii\base\Action` classes with too much branching/looping — logic that belongs in a service                                       |
| [`noComplexControllerActions`](#complexity-limits)                      | The same, for controller actions                                                                                                             |
| [`noControllerActionCallsViaThis`](#no-calling-actions-via-this)        | `$this->actionFoo()` inside a controller instead of a redirect or shared method                                                              |
| [`noDbQueriesInActions`](#no-database-access-outside-repositories)      | Direct DB/ActiveRecord access in `Action` classes                                                                                            |
| [`noDbQueriesInControllers`](#no-database-access-outside-repositories)  | Direct DB/ActiveRecord access in controllers                                                                                                 |
| [`noDbQueriesInViews`](#no-database-access-outside-repositories)        | Direct DB/ActiveRecord access in view files                                                                                                  |
| [`noDirectSuperglobals`](#no-raw-superglobals)                          | Direct use of `$_GET`, `$_POST`, `$_SESSION`, etc.                                                                                           |
| [`noDynamicQueryWhere`](#no-dynamic-sql-strings)                        | String-concatenated conditions passed to `Query::where()` / `andWhere()`                                                                     |
| [`noForbiddenYiiAppProperties`](#taming-yiiapp)                         | Reads of arbitrary `yii\base\Application` components, including `Yii::$app->*`                                                               |
| [`noRedundantHtmlEncode`](#no-redundant-htmlencode)                     | `Html::encode()` calls whose argument is always a `numeric-string`                                                                           |
| [`noYiiAppPropertyMutation`](#taming-yiiapp)                            | Writes to `yii\base\Application` properties, including `setComponents()`                                                                     |

Every rule ships with its own PHPStan error identifier (`mspirkovYii2Rules.*`), so you can target `ignoreErrors` precisely instead of silencing a whole rule.

## Installation

```bash
php composer.phar require --dev mspirkov/yii2-phpstan-rules
```

If your project uses [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer), the rules are picked up automatically — nothing else to do.

Otherwise, include them manually in your `phpstan.neon`:

```neon
includes:
    - vendor/mspirkov/yii2-phpstan-rules/rules.neon
```

## Configuration

All rules are on by default. Turn the whole set off, or tune individual rules, under `parameters.mspirkovYii2Rules`:

```neon
parameters:
    mspirkovYii2Rules:
        # Component IDs treated as "the database" by the DB-access rules
        yiiAppDbProperties:
            - db

        # Thresholds for the complexity rules — exceeding any one flags the method
        actionComplexity:
            ifCount: 3
            foreachCount: 0
            forCount: 0
            whileCount: 0
            doWhileCount: 0
            switchCount: 0
            matchCount: 0
            ternaryCount: 1
            tryCatchCount: 1

        # Yii application properties allowed to be read anywhere (e.g. request-agnostic settings)
        noForbiddenYiiAppProperties:
            allowedProperties:
                - id
                - name
                - charset
                - language
                - timeZone

        # Project-specific model validator aliases
        modelRulesValidation:
            customValidators:
                slug: app\validators\SlugValidator

        # Disable a single rule without touching the rest
        noDynamicQueryWhere:
            enabled: false
```

## The rules

### Active Form field validation

`ActiveForm::field($model, $attribute)` binds an editable input to the attribute: it reads the current value to render the input, and writes the submitted value back to the model on `load()`. This rule checks that the attribute is both readable and writable — a declared (non-readonly) property, a PHPDoc `@property`, or a matching getter/setter pair — and reports it whether it's missing entirely or only exists as read-only or write-only.

```php
/**
 * @property string $email
 * @property-read string $fullName
 */
final class ContactModel extends Model
{
    public $name;

    public function getPhone(): string
    {
        // ...
    }

    public function setPhone(string $phone): void
    {
        // ...
    }
}
```

```php
/** @var ContactModel $model */

$form = ActiveForm::begin();

echo $form->field($model, 'name');     // ✓ declared property
echo $form->field($model, 'email');    // ✓ declared via @property
echo $form->field($model, 'phone');    // ✓ has both getPhone() and setPhone()
echo $form->field($model, 'fullName'); // ✗ read-only — declared via @property-read, nothing to write the submitted value back to
echo $form->field($model, 'nickname'); // ✗ typo — "nickname" is not a property on ContactModel

ActiveForm::end();
```

### Active Record relations validation

`hasOne()` and `hasMany()` relation links are plain string arrays: the array keys belong to the related AR class, and the values belong to the current AR class. This rule checks that those properties exist, including properties declared through PHPDoc `@property`.

```php
/**
 * @property int $id
 * @property int $customer_id
 * @property int $shipping_address_id
 */
final class Order extends ActiveRecord
{
    public function getShippingAddress(): ActiveQuery
    {
        // ✗ missing property "uuid" on Address
        return $this->hasOne(Address::class, ['uuid' => 'shipping_address_id']);
    }

    public function getItems(): ActiveQuery
    {
        // ✗ missing property "order_uuid" on Order
        return $this->hasMany(OrderItem::class, ['order_id' => 'order_uuid']);
    }

    public function getCustomer(): ActiveQuery
    {
        // ✓
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }
}

/**
 * @property int $id
 */
final class Customer extends ActiveRecord
{
}

/**
 * @property int $id
 */
final class Address extends ActiveRecord
{
}

/**
 * @property int $id
 * @property int $order_id
 */
final class OrderItem extends ActiveRecord
{
}
```

### Component behaviors validation

`Component::behaviors()` uses Yii object configs, so typos usually wait until runtime. This rule checks statically visible behavior definitions on `yii\base\Component` subclasses, including models: class strings, `class` / `__class` config arrays, direct `Behavior` instances, unknown classes, classes that do not extend `yii\base\Behavior`, unknown config options, and option value types inferred from public properties or setters.

```php
public function behaviors(): array
{
    return [
        'timestamp' => [
            'class' => TimestampBehavior::class,
            'createdAtAtribute' => 'created_at',     // ✗ typo — unknown option
        ],
        'typecast' => [
            'class' => AttributeTypecastBehavior::class,
            'attributeTypes' => [
                'views_count' => AttributeTypecastBehavior::TYPE_INTEGER,
                'is_published' => AttributeTypecastBehavior::TYPE_BOOLEAN,
            ],
            'typecastAfterValidate' => 1,            // ✗ bool expected
        ],
        'invalid' => stdClass::class,                // ✗ not a yii\base\Behavior

        'slug' => [
            'class' => SluggableBehavior::class,
            'attribute' => 'title',                  // ✓
        ],
    ];
}
```

### `Yii::createObject()` validation

`Yii::createObject()`'s `class` / `__class` config array is declared as an open, all-optional PHPStan array shape (`array{class?: class-string<T>, __class?: class-string<T>, ...}`), so PHPStan itself already flags an unknown or wrong-typed `class` value — but it stays silent about a missing `class`/`__class` key entirely (just an unhelpful "unable to resolve the template type" note) and about every other key in the array, since `...` accepts anything. This rule fills exactly those two gaps on calls to `createObject()` on `Yii` (or any class extending `yii\BaseYii`): a clear "must specify class or __class" message, plus config keys checked against the resolved class's writable properties and value types, the same way `componentBehaviorsValidation` checks behaviors. Callables (a `Closure`, or a `[$target, 'method']` array) are left alone, since they are not object configs.

```php
Yii::createObject([
    'traceLevel' => 3,        // ✗ missing "class" or "__class"
]);

Yii::createObject([
    'class' => Logger::class,
    'traceLevel' => '3',      // ✗ wrong type — int expected
    'flushInteval' => 1000,   // ✗ typo — unknown option (should be "flushInterval")
]);

Yii::createObject([
    'class' => Logger::class,
    'traceLevel' => 3,        // ✓
]);
```

### Model attribute labels validation

`Model::attributeLabels()` is just as easy to get wrong as `rules()` — a typo'd key silently falls back to the default humanized attribute name instead of showing your label. This rule checks that every key is an existing property on the model (as a declared property or a PHPDoc `@property`, same resolution as `modelRulesValidation`) and isn't left empty:

```php
/**
 * @property string $email
 */
final class ContactModel extends Model
{
    public $name;

    public function attributeLabels(): array
    {
        return [
            'name' => 'Name',
            'emial' => 'E-mail',   // ✗ typo — "emial" is not a property on ContactModel
            'email' => 'E-mail',   // ✓ declared via @property
        ];
    }
}
```

### Model validation rules validation

`Model::rules()` is just a plain array — PHP will never tell you that you forgot a validator's required option, wrote an invalid regex, misconfigured one of its options, or targeted an attribute that doesn't even exist. For every rule entry the validator type resolves to (a built-in alias like `required`/`string`/`number`/`compare`/`date`/`match`/`in`/`unique`/`exist`/`file`/`image`/`ip`/`url`, a custom `Validator` subclass, a configured project alias, or an inline closure/method), this rule statically checks the option array against what that validator actually accepts and requires. A validator name it can't resolve is reported as an error; add project-specific aliases under `modelRulesValidation.customValidators`:

```php
public function rules(): array
{
    return [
        ['email', 'string', 'lenght' => 255],             // ✗ typo — unknown option "lenght" for StringValidator
        ['code', 'match', 'pattern' => '/[/'],            // ✗ invalid regular expression
        ['ip', 'ip', 'ipv4' => false, 'ipv6' => false],   // ✗ disables both protocols
        ['message', 'string', 'max' => 'invalid'],        // ✗ 'max' must be int|null
        ['status', 'someUnregisteredAlias'],              // ✗ unknown validator

        ['name', 'string', 'max' => 255],                 // ✓
    ];
}
```

This rule also checks that the attribute names at index 0 of each rule (including array lists of attributes) actually exist on the model, the same way `activeRecordRelationValidation` checks relation links — as a declared property or a PHPDoc `@property`. It only reports on attribute names it can resolve to a literal or constant string; anything built dynamically at runtime is left alone.

```php
/**
 * @property string $email
 */
final class ContactModel extends Model
{
    public $name;

    public function rules(): array
    {
        return [
            ['name', 'required'],
            ['emial', 'required'],   // ✗ typo — "emial" is not a property on ContactModel
            ['email', 'string'],     // ✓ declared via @property
        ];
    }
}
```

### Widget properties validation

`Widget::begin($config)` / `Widget::widget($config)` configs are just arrays, like `behaviors()`, so a typo'd key or a wrong-typed value only fails once the widget renders. This rule checks config keys against the called widget's writable properties and literal values against their declared types.

```php
ActiveForm::begin([
    'method' => 'get',             // ✓ declared property
    'metod' => 'get',              // ✗ typo — unknown option "metod"
    'encodeErrorSummary' => 'yes', // ✗ wrong type — bool expected, string given
]);

ActiveForm::end();
```

### Complexity limits

`noComplexActionClasses` and `noComplexControllerActions` count `if`, `foreach`, `for`, `while`, `do-while`, `switch`, `match`, ternaries, and `try/catch` blocks inside a controller action or `Action::run()`. Cross any configured threshold and the rule fires, pointing at the exact construct that pushed it over:

```php
// ✗ flagged: 4 `if` statements against a default limit of 3
public function actionCheckout(): string
{
    if ($this->cart->isEmpty()) { /* ... */ }
    if (!$this->cart->hasPaymentMethod()) { /* ... */ }
    if ($this->cart->hasOutOfStockItems()) { /* ... */ }
    if ($this->cart->hasExpiredCoupon()) { /* ... */ }

    return $this->render('checkout', ['cart' => $this->cart]);
}

// ✓ the decision tree moves to a service, the action just orchestrates
public function actionCheckout(): string
{
    return $this->render('checkout', $this->checkoutService->process($this->cart));
}
```

### No calling actions via `$this`

```php
// ✗ flagged: bypasses the action-resolution pipeline (filters, events, results)
public function actionEdit(int $id): Response
{
    return $this->actionView($id);
}

// ✓ redirect, or extract the shared part into a private method / service
public function actionEdit(int $id): Response
{
    return $this->redirect(['view', 'id' => $id]);
}
```

### No database access outside repositories

Fires on `ActiveRecord::find()`/`findOne()`/`save()`, `Yii::$app->db`, `Yii::$app->db->createCommand()`, creating or configuring a `Query`, transactions, and friends — wherever they turn up in a controller, an `Action`, or a view file.

```php
// ✗ flagged in a view: queries the database instead of just rendering data
<?php foreach (Post::find()->where(['status' => 1])->all() as $post): ?>

// ✓ the controller/action fetches the data, the view only renders it
<?php foreach ($posts as $post): ?>
```

`noDbQueriesInActions` / `noDbQueriesInControllers` push the same query building into a repository or service instead. Query builder setup counts too: `new Query()`, `$query->where()`, and dynamic calls on a `Query` object are all treated as direct database access in these layers.

### No raw superglobals

```php
// ✗ flagged, with the fix suggested in the error message
$id = $_GET['id'];

// ✓ read through the injected yii\web\Request instead
$id = $this->request->get('id');
```

Covers `$_GET`, `$_POST`, `$_REQUEST`, `$_SESSION`, `$_COOKIE`, `$_FILES`, and `$_SERVER`, each pointing at the matching `yii\web\Request` / `Session` / `UploadedFile` API.

### No dynamic SQL strings

```php
// ✗ flagged: string-built condition, one step from SQL injection
$query->where("status = $status");
$query->where('status = ' . $status);

// ✓ array condition syntax — parameterized, and PHPStan can see the shape
$query->where(['status' => $status]);
```

### Taming `Yii::$app`

Two rules keep the application object from becoming a place where any property can be read or reassigned from anywhere. They check expressions typed as `yii\base\Application`, so the same restrictions apply to direct `Yii::$app` usage, variables holding it, and explicit `Application` instances:

```php
// ✗ noForbiddenYiiAppProperties: arbitrary component access
$cache = Yii::$app->cache;

$app = Yii::$app;
$request = $app->request;

$application = new Application($config);
$session = $application->session;

// ✗ noYiiAppPropertyMutation: mutating the container at runtime
Yii::$app->params = [];
Yii::$app->setComponents([...]);

$app->language = 'ru-RU';
$application->setComponents([...]);

// ✓ inject the component instead
public function __construct(private CacheInterface $cache) {}
```

A short allowlist (`id`, `name`, `charset`, `language`, `timeZone` by default) stays available everywhere since those are effectively static configuration, not injectable services.

### No redundant `Html::encode()`

PHPStan already flags most nonsensical `Html::encode()` calls on its own (wrong argument types and the like). The one gap it doesn't cover is a `numeric-string` argument: a value PHPStan can already prove only ever holds digits, so escaping it can't do anything — `htmlspecialchars()` never touches a plain number. This rule fires only in that narrow case, on `yii\helpers\Html` / `BaseHtml` and their subclasses:

```php
/**
 * @var numeric-string $id
 * @var string $name
 */

echo Html::encode($id);   // ✗ flagged — $id can only ever be a numeric-string
echo Html::encode($name); // ✓ a plain string may still contain special characters
```
