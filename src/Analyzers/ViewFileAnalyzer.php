<?php

declare(strict_types=1);

namespace MSpirkov\Yii2\PHPStan\Analyzers;

use MSpirkov\Yii2\PHPStan\Resolvers\ExpressionValueResolver;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use yii\base\Controller;
use yii\helpers\Inflector;

final class ViewFileAnalyzer
{
    private const CONTROLLER_SUFFIX = 'Controller';

    /** @var list<string> */
    private const CONTROLLERS_DIRECTORIES = ['controllers', 'Controllers'];

    /** @var list<string> */
    private const VIEWS_DIRECTORIES = ['views', 'Views'];

    private const MAX_ALIAS_DEPTH = 10;

    /** @var array<string, string> */
    private array $aliases;

    /** @var list<string> */
    private array $extensions;

    private ExpressionValueResolver $expressionValueResolver;

    /**
     * @param array<string, string> $aliases
     * @param list<string> $extensions
     */
    public function __construct(
        array $aliases,
        array $extensions,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->aliases = $aliases;
        $this->extensions = $extensions;
        $this->expressionValueResolver = $expressionValueResolver;
    }

    public function isViewFile(string $file): bool
    {
        return $this->findViewsDirectory($file) !== null;
    }

    public function getViewName(MethodCall $call, Scope $scope): ?string
    {
        $viewArg = $call->getArgs()[0] ?? null;
        if ($viewArg === null || $viewArg->unpack) {
            return null;
        }

        return $this->expressionValueResolver->getSingleStringValue($viewArg->value, $scope);
    }

    /**
     * @return array{message: string, tip: string|null}|null
     */
    public function findControllerViewProblem(ClassReflection $controller, string $view): ?array
    {
        return $this->findViewProblem($view, $this->locateControllerViews($controller));
    }

    /**
     * @return array{message: string, tip: string|null}|null
     */
    public function findRenderedViewProblem(?string $viewFile, string $view): ?array
    {
        return $this->findViewProblem(
            $view,
            $viewFile === null ? null : $this->locateViewFileViews($viewFile)
        );
    }

    /**
     * @param array{viewsDirectory: string, directory: string}|null $location
     *
     * @return array{message: string, tip: string|null}|null
     */
    private function findViewProblem(string $view, ?array $location): ?array
    {
        $resolution = $this->resolveViewPath($view, $location);
        if ($resolution['unresolvedAlias'] !== null) {
            return [
                'message' => sprintf(
                    'View "%s" uses the alias "%s" that cannot be resolved. Add it to the "mspirkovYii2Rules.aliases" parameter.',
                    $view,
                    $resolution['unresolvedAlias']
                ),
                'tip' => null,
            ];
        }

        if ($resolution['path'] === null || $this->viewFileExists($resolution['path'])) {
            return null;
        }

        return [
            'message' => sprintf('View "%s" does not exist. Check the view name or create the view file.', $view),
            'tip' => $resolution['alias'] === null
                ? null
                : sprintf(
                    'The view path is built from the "%s" alias set in the "mspirkovYii2Rules.aliases" parameter. It may point to the wrong directory.',
                    $resolution['alias']
                ),
        ];
    }

    /**
     * @param array{viewsDirectory: string, directory: string}|null $location
     *
     * @return array{path: string|null, alias: string|null, unresolvedAlias: string|null}
     */
    private function resolveViewPath(string $view, ?array $location): array
    {
        if (strncmp($view, '@', 1) === 0) {
            return $this->resolveAliasedPath($view);
        }

        if (strncmp($view, '//', 2) === 0) {
            $resolution = $this->resolveAliasedPath('@app');
            if ($resolution['path'] !== null) {
                $resolution['path'] = $this->findViewsDirectoryIn($resolution['path']) . '/' . ltrim($view, '/');
            }

            return $resolution;
        }

        if ($location === null) {
            return ['path' => null, 'alias' => null, 'unresolvedAlias' => null];
        }

        $path = strncmp($view, '/', 1) === 0
            ? $location['viewsDirectory'] . '/' . ltrim($view, '/')
            : $location['directory'] . '/' . $view;

        return ['path' => $path, 'alias' => null, 'unresolvedAlias' => null];
    }

    /**
     * @return array{path: string|null, alias: string|null, unresolvedAlias: string|null}
     */
    private function resolveAliasedPath(string $alias): array
    {
        $resolved = $this->resolveAlias($alias);

        return [
            'path' => $resolved['path'],
            'alias' => $this->getAliasRoot($alias),
            'unresolvedAlias' => $resolved['unresolvedRoot'],
        ];
    }

    private function getAliasRoot(string $alias): string
    {
        $separatorPosition = strpos($alias, '/');

        return $separatorPosition === false ? $alias : substr($alias, 0, $separatorPosition) . '';
    }

    /**
     * @return array{path: string|null, unresolvedRoot: string|null}
     */
    private function resolveAlias(string $alias, int $depth = 0): array
    {
        $root = $this->getAliasRoot($alias);
        $rest = substr($alias, strlen($root));

        $path = $this->aliases[$root] ?? null;
        if ($path !== null && strncmp($path, '@', 1) === 0) {
            $resolved = $depth < self::MAX_ALIAS_DEPTH
                ? $this->resolveAlias($path, $depth + 1)
                : ['path' => null, 'unresolvedRoot' => $root];

            if ($resolved['path'] === null) {
                return $resolved;
            }

            $path = $resolved['path'];
        }

        return $path === null
            ? ['path' => null, 'unresolvedRoot' => $root]
            : ['path' => rtrim($path, '/\\') . $rest, 'unresolvedRoot' => null];
    }

    /**
     * @return array{viewsDirectory: string, directory: string}|null
     */
    private function locateControllerViews(ClassReflection $controller): ?array
    {
        if ($controller->isAbstract()) {
            return null;
        }

        $fileName = $controller->getFileName();
        if ($fileName === null) {
            return null;
        }

        $segments = explode('\\', $controller->getName());
        $shortName = array_pop($segments);
        if (substr($shortName, -strlen(self::CONTROLLER_SUFFIX)) !== self::CONTROLLER_SUFFIX) {
            return null;
        }

        $controllersIndexes = array_keys(array_filter(
            $segments,
            static fn(string $segment): bool => in_array($segment, self::CONTROLLERS_DIRECTORIES, true)
        ));

        if ($controllersIndexes === []) {
            return null;
        }

        $name = substr($shortName, 0, -strlen(self::CONTROLLER_SUFFIX)) . '';
        if ($name === '') {
            return null;
        }

        if ($controller->getNativeMethod('getViewPath')->getDeclaringClass()->getName() !== Controller::class) {
            return null;
        }

        $prefixSegments = array_slice($segments, end($controllersIndexes) + 1);
        $controllersDirectory = dirname($fileName, count($prefixSegments) + 1);
        if (!in_array(basename($controllersDirectory), self::CONTROLLERS_DIRECTORIES, true)) {
            return null;
        }

        $viewsDirectory = $this->findViewsDirectoryIn(dirname($controllersDirectory));
        $controllerId = implode('/', array_merge($prefixSegments, [Inflector::camel2id($name)]));

        return ['viewsDirectory' => $viewsDirectory, 'directory' => $viewsDirectory . '/' . $controllerId];
    }

    /**
     * @return array{viewsDirectory: string, directory: string}|null
     */
    private function locateViewFileViews(string $viewFile): ?array
    {
        $viewsDirectory = $this->findViewsDirectory($viewFile);

        return $viewsDirectory === null ? null : ['viewsDirectory' => $viewsDirectory, 'directory' => dirname($viewFile)];
    }

    private function findViewsDirectory(string $file): ?string
    {
        $directory = dirname($file);

        while (dirname($directory) !== $directory) {
            if (in_array(basename($directory), self::VIEWS_DIRECTORIES, true)) {
                return $directory;
            }

            $directory = dirname($directory);
        }

        return null;
    }

    private function findViewsDirectoryIn(string $parent): string
    {
        foreach (self::VIEWS_DIRECTORIES as $name) {
            if (is_dir($parent . '/' . $name)) {
                return $parent . '/' . $name;
            }
        }

        return $parent . '/' . self::VIEWS_DIRECTORIES[0];
    }

    private function viewFileExists(string $viewPath): bool
    {
        if (pathinfo($viewPath, PATHINFO_EXTENSION) !== '') {
            return is_file($viewPath);
        }

        foreach ($this->extensions as $extension) {
            if (is_file($viewPath . '.' . $extension)) {
                return true;
            }
        }

        return false;
    }
}
