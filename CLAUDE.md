# yii2-phpstan-rules

A PHPStan extension: a set of custom rules that check Yii2-specific patterns (see [README.md](README.md) for the full, user-facing catalogue and rationale for each rule). This file is about the codebase itself — how it's organized and how to extend it correctly.

## Layout

```text
src/
  Rules/       PHPStan Rule<T> implementations — one class per check, this is where errors are reported
  Analyzers/   Stateless helpers shared across rules (type/class/config inspection) — the mandatory home for any logic used by more than one rule, see "Reuse Analyzers" below
  Finders/     AST search helpers (traverse a node tree, collect matches)
  Visitors/    PhpParser NodeVisitor implementations used by Finders
  Resolvers/   Small "what does this expression statically resolve to" helpers
tests/
  Rules/*Test.php        one test class per rule, extends AbstractTestCase
  Rules/Data/<RuleName>/ fixture PHP analysed by the rule under test (asserted against)
  Rules/Source/<RuleName>/ supporting classes the Data fixture references (one class per file — see below)
  Rules/Config/<RuleName>/ .neon overrides for tests that exercise non-default configuration
rules.neon     the extension's DI wiring: parameters, parametersSchema, conditionalTags, services
```

Every rule ships its own PHPStan error identifier (`mspirkovYii2Rules.<name>`) via `src/Rules/Identifiers.php` + `src/Rules/ErrorBuilder.php`, so consumers can `ignoreErrors` a single check by identifier instead of disabling the whole rule.

## Adding a new rule

Follow the existing rules as templates (`NoDirectSuperglobalsRule` for a minimal example, `NoDynamicQueryWhereRule` or `DbQueriesUsageAnalyzer` for one that inspects call targets/types). The moving parts, in order:

1. **`src/Rules/Identifiers.php`** — add a `PREFIX . 'camelCaseName'` constant and append it to `LIST`.
2. **`src/Rules/<Name>Rule.php`** — implements `Rule<SomeNodeType>` (`@implements Rule<...>` docblock + matching `getNodeType()`). Return `[]` early for anything that doesn't match; build errors with `ErrorBuilder::build($message, Identifiers::X, $node->getStartLine())`.
   - Prefer composing existing `Analyzers/*` (especially `ExpressionTypeAnalyzer::isClassNameOf()` / `isObjectOf()`) over re-deriving class-reflection logic inline — most "is this expression an instance of X" checks already exist there.
   - To resolve a `Name` node (e.g. the class part of a `StaticCall`) to a string, use `$scope->resolveName($name)`, then check it with the analyzer above.
3. **`rules.neon`** — four places to touch, all keyed by the same `camelCaseName`:
   - `parameters.mspirkovYii2Rules.<name>.enabled: %mspirkovYii2Rules.enableAllRules%` (plus any rule-specific options)
   - matching block under `parametersSchema`
   - `conditionalTags` entry: `<FQCN>: { phpstan.rules.rule: %mspirkovYii2Rules.<name>.enabled% }`
   - `services` entry for the class (only add `arguments:` if the constructor needs config values that aren't other autowired services — dependencies on other `Analyzers`/rule services are autowired by type, no `arguments:` needed)
4. **Tests** — `tests/Rules/<Name>RuleTest.php` extends `AbstractTestCase`, asserts `[message, line]` pairs against `tests/Rules/Data/<Name>/code.php`.
   - Any class the fixture needs by name (not an anonymous class or an inline literal) must live in `tests/Rules/Source/<Name>/<ClassName>.php`, one class per file, matching PSR-4 (`MSpirkov\Yii2\PHPStan\Tests\Rules\Source\<Name>\<ClassName>`) — classes declared inline inside the `Data` fixture are **not** reflectable by PHPStan's test reflection provider (no autoload entry maps `code.php` to them), and referencing them by name will fail with "not found in ReflectionProvider".
   - Both `tests/*/Data/*` and `tests/*/Source/*` are excluded from this project's own `phpstan.dist.neon` / `rector.php` analysis — write fixtures that would normally fail PHPStan/type checks freely when the point is to exercise a code path (e.g. a call with a deliberately wrong argument count).
5. **README.md** — add a row to the rules table and a `###` section under "The rules"; the table's link must match the GitHub-generated anchor slug of that heading.

## Verifying a change

Run all four before considering a rule done — they check different things and a rule can pass one while failing another:

```bash
vendor/bin/phpunit                                    # correctness of the new rule (needs XDEBUG_MODE=coverage for coverage reports)
vendor/bin/phpstan analyse --no-progress               # this repo's own source lints clean at level max
vendor/bin/php-cs-fixer check                           # code style (fix with `check` -> `fix`)
vendor/bin/rector process --dry-run                     # no leftover simplifications rector would apply
```

Coverage is expected to stay at 100% (`tests/coverage/`, tracked via Codecov) — a new rule needs fixture cases hitting every early-return branch, not just the "fires" case. When unsure whether a branch is covered, generate the clover report and grep for `count="0"` lines in the new file rather than guessing from the fixture alone.

## Conventions worth knowing

- **Reuse Analyzers, don't reimplement them.** Before writing any type-check, class-check, or config-parsing logic in a rule, check whether `Analyzers/*` already does it — and if you're about to write something a second rule could plausibly need, put it in an `Analyzer` (constructor-injected, autowired via `rules.neon`) instead of inlining it. This project has been bitten by the same "is this class X or a subclass" / "is this expression an instance of X" logic being hand-rolled per rule before `ExpressionTypeAnalyzer` existed; don't regress that. What's already there:
  - `ExpressionTypeAnalyzer` — `isClassNameOf()` / `isClassReflectionOf()` (class-string or `ClassReflection` is-or-extends a given class), `isObjectOf()` (expression's type is-or-extends a class), `isDefinitelyNotString()` / `isDefinitelyNotStringOrArrayOfStrings()` / `isDefinitelyNotArrayOrObjectOf()`, `hasClass()`.
  - `YiiAppAnalyzer` — is an expression's (nullable) type `yii\base\Application`.
  - `DbQueriesUsageAnalyzer` — does a node/subtree directly produce or use a DB/ActiveRecord object.
  - `BaseObjectPropertyAnalyzer` — does a Yii `BaseObject`/model class have a given property (declared, PHPDoc `@property`, or getter/setter), including the "does this look like a real property name" heuristic for free-form config keys.
  - `BaseObjectConfigAnalyzer`, `ComponentConfigMethodAnalyzer`, `ComponentObjectConfigAnalyzer` — object/array config shape validation (`class`/`__class` keys, config-array vs. instance vs. closure forms) used by the `behaviors()`/`rules()`/`attributeLabels()` rules.
  - `ActionComplexityAnalyzer` — branch/loop counting against configured thresholds.
  - `Finders/MethodReturnExpressionFinder` + `Visitors/MethodReturnExpressionVisitor` — collect every expression a method could return.
  - `Resolvers/ExpressionValueResolver` — resolve an expression to a constant bool/string or check it's a named function call / literal `null`, when the expression isn't already a literal node.

  If none of these fit, extend the closest one rather than adding a near-duplicate method, and only fall back to writing rule-local logic when it's genuinely specific to that one rule's error shape.
- Rule classes are `final`, take no rule-specific constructor args unless config-driven, and stay side-effect-free (`processNode()` only reads `Node`/`Scope`, never mutates).
- Type checks always go through PHPStan's `Type` API (`->isString()->yes()`, `->isNumericString()->yes()`, etc.), never `instanceof` on a `Type` implementation — the file-level docblock in PHPStan's own `Type` interface calls this out explicitly, and it breaks on union/intersection types.
- `ClassReflection::is()` / `isSubclassOf()` / `implementsInterface()` is how "is this the class I care about, or one of its subclasses" gets checked once a `ClassReflection` is in hand.
- Messages are one or two full sentences, second sentence (if present) is the actionable fix ("Use X instead."), no trailing punctuation quirks — match the tone already in `src/Rules/*.php`.
