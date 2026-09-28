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

    /** @var array<string, string> */
    private array $aliases;

    /** @var list<string> */
    private array $extensions;

    /** @var array<string, string> */
    private array $viewPaths;

    /** @var array<string, string>|null */
    private ?array $viewDirectories = null;

    private ExpressionValueResolver $expressionValueResolver;

    /**
     * @param array<string, string> $aliases
     * @param list<string> $extensions
     * @param array<string, string> $viewPaths
     */
    public function __construct(
        array $aliases,
        array $extensions,
        array $viewPaths,
        ExpressionValueResolver $expressionValueResolver
    ) {
        $this->aliases = $aliases;
        $this->extensions = $extensions;
        $this->viewPaths = $viewPaths;
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
            'message' => sprintf(
                'View "%s" does not exist at "%s". Check the view name or create the view file.',
                $view,
                $this->getExpectedViewFile($resolution['path'])
            ),
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
     * @return array{
     *     path: string|null,
     *     alias: string|null,
     *     unresolvedAlias: string|null,
     * }
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

        return [
            'path' => $path,
            'alias' => null,
            'unresolvedAlias' => null,
        ];
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
    private function resolveAlias(string $alias): array
    {
        $root = $this->getAliasRoot($alias);
        $path = $this->aliases[$root] ?? null;
        if ($path === null) {
            return [
                'path' => null,
                'unresolvedRoot' => $root,
            ];
        }

        return [
            'path' => rtrim($path, '/\\') . substr($alias, strlen($root)),
            'unresolvedRoot' => null,
        ];
    }

    /**
     * @return array{viewsDirectory: string, directory: string}|null
     */
    private function locateControllerViews(ClassReflection $controller): ?array
    {
        if ($controller->isAbstract()) {
            return null;
        }

        $segments = explode('\\', $controller->getName());
        $shortName = array_pop($segments);
        if (substr($shortName, -strlen(self::CONTROLLER_SUFFIX)) !== self::CONTROLLER_SUFFIX) {
            return null;
        }

        $name = substr($shortName, 0, -strlen(self::CONTROLLER_SUFFIX)) . '';
        if ($name === '') {
            return null;
        }

        if ($controller->getNativeMethod('getViewPath')->getDeclaringClass()->getName() !== Controller::class) {
            return null;
        }

        $configuredNamespace = $this->findConfiguredNamespace($segments);
        if ($configuredNamespace !== null) {
            return $this->createControllerLocation(
                $this->getViewDirectories()[$configuredNamespace],
                array_slice($segments, substr_count($configuredNamespace, '\\') + 1),
                $name
            );
        }

        $controllersIndexes = array_keys(array_filter(
            $segments,
            static fn(string $segment): bool => in_array($segment, self::CONTROLLERS_DIRECTORIES, true)
        ));

        if ($controllersIndexes === []) {
            return null;
        }

        /** @var string $controllerFilename */
        $controllerFilename = $controller->getFileName();
        $prefixSegments = array_slice($segments, end($controllersIndexes) + 1);
        $controllersDirectory = dirname($controllerFilename, count($prefixSegments) + 1);
        if (!in_array(basename($controllersDirectory), self::CONTROLLERS_DIRECTORIES, true)) {
            return null;
        }

        return $this->createControllerLocation(
            $this->findViewsDirectoryIn(dirname($controllersDirectory)),
            $prefixSegments,
            $name
        );
    }

    /**
     * @param list<string> $namespaceSegments
     */
    private function findConfiguredNamespace(array $namespaceSegments): ?string
    {
        $namespace = implode('\\', $namespaceSegments);

        foreach (array_keys($this->getViewDirectories()) as $configuredNamespace) {
            if (
                $namespace === $configuredNamespace
                || strncmp($namespace, $configuredNamespace . '\\', strlen($configuredNamespace) + 1) === 0
            ) {
                return $configuredNamespace;
            }
        }

        return null;
    }

    /**
     * @param list<string> $prefixSegments
     *
     * @return array{viewsDirectory: string, directory: string}
     */
    private function createControllerLocation(string $viewsDirectory, array $prefixSegments, string $name): array
    {
        $controllerId = implode('/', array_merge($prefixSegments, [Inflector::camel2id($name)]));

        return ['viewsDirectory' => $viewsDirectory, 'directory' => $viewsDirectory . '/' . $controllerId];
    }

    /**
     * @return array{viewsDirectory: string, directory: string}|null
     */
    private function locateViewFileViews(string $viewFile): ?array
    {
        $viewsDirectory = $this->findViewsDirectory($viewFile);
        if ($viewsDirectory === null) {
            return null;
        }

        return [
            'viewsDirectory' => $viewsDirectory,
            'directory' => dirname($viewFile),
        ];
    }

    private function findViewsDirectory(string $file): ?string
    {
        foreach ($this->getViewDirectories() as $configuredDirectory) {
            $realDirectory = realpath($configuredDirectory);
            if ($realDirectory !== false && strncmp($file, $realDirectory . '/', strlen($realDirectory) + 1) === 0) {
                return $realDirectory;
            }
        }

        $directory = dirname($file);

        while (dirname($directory) !== $directory) {
            if (in_array(basename($directory), self::VIEWS_DIRECTORIES, true)) {
                return $directory;
            }

            $directory = dirname($directory);
        }

        return null;
    }

    /**
     * Configured view directories keyed by namespace, the most specific namespace first.
     *
     * @return array<string, string>
     */
    private function getViewDirectories(): array
    {
        if ($this->viewDirectories === null) {
            $directories = [];
            foreach ($this->viewPaths as $namespace => $path) {
                $directories[trim($namespace, '\\')] = rtrim($path, '/\\');
            }

            uksort($directories, static fn(string $a, string $b): int => strlen($b) <=> strlen($a));
            $this->viewDirectories = $directories;
        }

        return $this->viewDirectories;
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

    private function getExpectedViewFile(string $viewPath): string
    {
        if (pathinfo($viewPath, PATHINFO_EXTENSION) !== '' || $this->extensions === []) {
            return $viewPath;
        }

        return $viewPath . '.' . $this->extensions[0];
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
