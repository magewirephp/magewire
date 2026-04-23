<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\Exception\FileSystemException;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompilerManager;
use Magewirephp\Magewire\Support\Pipeline;
use Throwable;

abstract class Compiler
{
    private bool $compile = true;

    private string|null $basePath = null;
    private string|null $targetPath = null;
    private float $compileStartTime = 0;

    // Fabricates (Lazy Initialization pattern).
    protected CompilerPipelines|null $compilerPipelines = null;

    public function __construct(
        private CompilerManager $manager,
        private CompilerPipelinesFactory $compilerPipelinesFactory
    ) {
    }

    /**
     * @throws FileSystemException|Throwable
     */
    public function compile(string $basePath, string $targetPath): bool
    {
        $this->basePath = $basePath;
        $this->targetPath = $targetPath;
        $this->compileStartTime = microtime(true);

        $filesystem = $this->management()->file()->system();
        $content = $this->compiler()->run($filesystem->read($basePath));

        $filesystem->ensureDirectoryExists($targetPath);
        $filesystem->write($content, $targetPath);

        return $filesystem->exists($targetPath);
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function targetPath(): string
    {
        return $this->targetPath;
    }

    public function compileStartTime(): float
    {
        return $this->compileStartTime;
    }

    /**
     * Compiler management entry point.
     */
    public function management(): CompilerManager
    {
        return $this->manager;
    }

    /**
     * @return CompilerPipelines
     */
    public function pipelines(): CompilerPipelines
    {
        return $this->compilerPipelines ??= $this->newCompilerPipelineDistributorInstance();
    }

    /**
     * Gets or sets the compile flag.
     *
     * When called without arguments, this method returns the current compile state.
     * When a boolean value is provided, it updates the compile flag and returns
     * the current instance for method chaining.
     */
    public function canCompile(bool|null $choice = null): bool|static
    {
        if ($choice) {
            $this->compile = $choice;
            return $this;
        }

        return $this->compile;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @throws FileSystemException
     */
    public function requiresRecompile(string $path): bool
    {
        $filesystem = $this->management()->file()->system();
        $compiled = $this->management()->file()->generateFilePath($path);

        if ($filesystem->exists($compiled)) {
            return $filesystem->lastModified($path) >= $filesystem->lastModified($compiled);
        }

        return true;
    }

    /**
     * Returns the template pipeline.
     */
    protected function compiler(): Pipeline
    {
        return $this->pipelines()->template();
    }

    /**
     * Compiles a PHP template string by iterating through its tokens.
     * @throws Throwable
     */
    protected function compileTokens(string $input): string
    {
        $output = '';

        foreach (token_get_all($input) as $token) {
            $output .= is_array($token) ? $this->parseToken($token) : $token;
        }

        return $output;
    }

    /**
     * Parses and compiles a single token from the tokenized template.
     * @throws Throwable
     */
    protected function parseToken(#[\SensitiveParameter] array $token): string
    {
        [$id, $content] = $token;

        if ($id == T_INLINE_HTML) {
            return $this->pipelines()->html()->run($content);
        }

        return $content;
    }

    /**
     * Compile directives starting with "@".
     */
    protected function compileDirectives(string $template): string
    {
        preg_match_all('/\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( [\S\s]*? ) \))?/x', $template, $matches);

        $offset = 0;

        for ($i = 0; isset($matches[0][$i]); $i++) {
            $match = [
                $matches[0][$i],
                $matches[1][$i],
                $matches[2][$i],
                $matches[3][$i] ?: null,
                $matches[4][$i] ?: null
            ];

            while (isset($match[4]) && str_ends_with($match[0], ')') && ! $this->management()->utils()->hasEvenNumberOfParentheses($match[0])) {
                $after = strstr($template, $match[0]);

                if ($after === false) {
                    break;
                }

                $after = substr($after, strlen($match[0]));
                $pos = strpos($after, ')');

                if ($pos === false) {
                    break;
                }

                $rest = substr($after, 0, $pos);

                if (isset($matches[0][$i + 1]) && str_contains($rest . ')', $matches[0][$i + 1])) {
                    unset($matches[0][$i + 1]);
                    $i++;
                }

                $match[0] .= $rest . ')';
                $match[3] .= $rest . ')';
                $match[4] .= $rest;
            }

            [$template, $offset] = $this->replaceFirstStatement($match[0], $this->compileDirective($match), $template, $offset);
        }

        return $template;
    }

    /**
     * Compile a single "@" directive.
     */
    protected function compileDirective(array $match): string
    {
        [$area, $directive] = $this->management()->directives()->tryToLocateArea($match[1]);

        if (str_contains($match[1], '@')) {
            $match[0] = isset($match[3]) ? $match[1] . $match[3] : $match[1];
        } elseif ($area instanceof DirectiveArea && is_string($directive)) {
            if ($area->responsibilities()->has($directive)) {
                $match[0] = $area->responsibilities()->pop($directive)->compile($match[4] ?? '', $directive);
            } elseif ($area->has($directive)) {
                $match[0] = $area->get($directive)->compile($match[4] ?? '', $directive);
            }
        } elseif ($directive = $this->management()->directives()->area()->get($directive)) {
            $match[0] = $directive->compile($match[4] ?? '', $match[1]);
        } else {
            return $match[0];
        }

        return isset($match[3]) ? $match[0] : $match[0] . $match[2];
    }

    /**
     * Replace the first match for a statement compilation operation.
     */
    protected function replaceFirstStatement(string $search, string $replace, string $subject, int $offset): array|string
    {
        if ($search === '') {
            return $subject;
        }

        $position = strpos($subject, $search, $offset);

        if ($position !== false) {
            return [
                substr_replace($subject, $replace, $position, strlen($search)),
                $position + strlen($replace)
            ];
        }

        return [$subject, 0];
    }

    protected function newCompilerPipelineDistributorInstance(): CompilerPipelines
    {
        $distributor = $this->compilerPipelinesFactory->create(['type' => Pipeline::class]);

        $distributor
            ->template()
            ->pipe(function (string $throughput, callable $next) {
                return $next($this->compileTokens($throughput));
            });

        // Reserves the 'security' groups at the very earliest position
        // so security-related pipes always run before everything else.
        $distributor->template()->middleware()->group('security', 0);
        $distributor->template()->middleware()->group('last', 900);
        $distributor->html()->middleware()->group('security', 0);

        $distributor
            ->html()
            ->pipe(function (string $throughput, callable $next) {
                return $next($this->compileDirectives($throughput));
            });

        return $distributor;
    }
}
