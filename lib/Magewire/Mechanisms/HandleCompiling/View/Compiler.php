<?php

/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Mechanisms\HandleCompiling\View;

use Magento\Framework\Exception\FileSystemException;
use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Management\CompilerManager;
use Magewirephp\Magewire\Support\Pipeline;
use Throwable;

/**
 * @mago-expect lint:too-many-methods
 * @mago-expect lint:cyclomatic-complexity
 * @mago-expect lint:kan-defect
 */
abstract class Compiler
{
    private bool $compile = true;

    private string|null $basePath = null;
    private string|null $targetPath = null;
    private float $compileStartTime = 0;

    // Fabricates (Lazy Initialization pattern).
    protected CompilerPipelines|null $compilerPipelines = null;

    public function __construct(
        private readonly CompilerManager $manager,
        private readonly CompilerPipelinesFactory $compilerPipelinesFactory
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

        if ($id === T_INLINE_HTML) {
            return $this->pipelines()->html()->run($content);
        }

        return $content;
    }

    /**
     * Compile `{{ $expr }}` (escaped) and `{!! $expr !!}` (raw) echo tags.
     *
     * `{{ $expr }}`           → `<?php echo $escaper->escapeHtml($expr) ?>`
     * `{{ attr($expr) }}`     → `<?php echo $escaper->escapeHtmlAttr($expr) ?>`
     * `{{ js($expr) }}`       → `<?php echo $escaper->escapeJs($expr) ?>`
     * `{{ css($expr) }}`      → `<?php echo $escaper->escapeCss($expr) ?>`
     * `{{ url($expr) }}`      → `<?php echo $escaper->escapeUrl($expr) ?>`
     * `{{ html($expr) }}`     → `<?php echo $escaper->escapeHtml($expr) ?>`
     * `{!! $expr !!}`         → `<?php echo $expr ?>`
     *
     * Runs only on inline-HTML tokens, so existing `<?php ?>` blocks are untouched.
     */
    protected function compileEchos(string $template): string
    {
        $template = preg_replace_callback('/\{!!\s*([\s\S]+?)\s*!!\}/', static fn (array $matches): string => sprintf('<?php echo %s ?>', $matches[1]), $template);

        return preg_replace_callback('/\{\{\s*([\s\S]+?)\s*\}\}/', fn (array $matches): string => sprintf('<?php echo %s ?>', $this->resolveEscaperCall($matches[1])), $template);
    }

    /**
     * Translate an echo expression into an `$escaper->escape*()` call.
     *
     * When the whole expression is wrapped in a known escape modifier
     * (`attr(...)`, `js(...)`, `css(...)`, `url(...)`, `html(...)`), strip
     * the wrapper and target the matching Escaper method directly. Otherwise
     * fall back to `escapeHtml()` on the raw expression.
     */
    private function resolveEscaperCall(string $expression): string
    {
        $modifiers = [
            'attr' => 'escapeHtmlAttr',
            'js' => 'escapeJs',
            'css' => 'escapeCss',
            'url' => 'escapeUrl',
            'html' => 'escapeHtml'
        ];

        if (preg_match('/^\s*(attr|js|css|url|html)\s*\(/', $expression, $head) === 1) {
            $modifier = $head[1];
            $openPos = strpos($expression, '(');
            $inner = $this->extractBalancedParens($expression, $openPos);

            if ($inner !== null) {
                return sprintf('$escaper->%s(%s)', $modifiers[$modifier], $inner);
            }
        }

        return sprintf('$escaper->escapeHtml(%s)', $expression);
    }

    /**
     * Walk from the opening `(` at $openPos until its matching `)`. Return the
     * inner expression if the closing paren is the LAST non-whitespace char,
     * else null (the expression is not a pure modifier wrap).
     */
    private function extractBalancedParens(string $expression, int $openPos): string|null
    {
        $length = strlen($expression);
        $depth = 1;
        $cursor = $openPos + 1;

        while ($cursor < $length && $depth > 0) {
            $char = $expression[$cursor];

            if ($char === '(') {
                $depth++;
            } elseif ($char === ')') {
                $depth--;
            }

            $cursor++;
        }

        if ($depth !== 0) {
            return null;
        }

        $closePos = $cursor - 1;

        if (trim(substr($expression, $closePos + 1)) !== '') {
            return null;
        }

        return substr($expression, $openPos + 1, $closePos - $openPos - 1);
    }

    /**
     * Compile directives starting with "@".
     *
     * @mago-expect lint:no-isset
     * @mago-expect lint:halstead
     * @mago-expect lint:no-shorthand-ternary
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

            // Re-derive the inner expression from the (now balanced) paren group. The non-greedy
            // capture and the re-balancing above can otherwise leave $match[4] missing closing
            // parens absorbed into $match[0], truncating nested calls like @translate(strtoupper('x')).
            if (isset($match[3])) {
                $match[4] = substr($match[3], 1, -1);
            }

            [$template, $offset] = $this->replaceFirstStatement($match[0], $this->compileDirective($match), $template, $offset);
        }

        return $template;
    }

    /**
     * Compile a single "@" directive.
     *
     * @mago-expect lint:no-isset
     * @mago-expect lint:halstead
     * @mago-expect lint:no-assign-in-condition
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
                return $next($this->compileEchos($throughput));
            });

        $distributor
            ->html()
            ->pipe(function (string $throughput, callable $next) {
                return $next($this->compileDirectives($throughput));
            });

        return $distributor;
    }
}
