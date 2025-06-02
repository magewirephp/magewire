<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireCompiling\View;

use Magento\Framework\Filesystem\DirectoryList;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\Management\CompileManager;
use Magewirephp\Magewire\Support\Pipeline;
use Magewirephp\Magewire\Support\PipelineFactory;

abstract class Compiler
{
    /**
     * All the available compiler function affixes.
     * @var string[]
     */
    protected array $compilers = [];

    private bool $compile = true;

    protected string $resourcePath;

    private Pipeline|null $precompiler = null;
    private Pipeline|null $optimizer = null;

    public function __construct(
        private readonly FileSystem $filesystem,
        private readonly DirectoryList $directoryList,
        private readonly CompileManager $manager,
        private readonly CompilerUtils $utils,
        private readonly PipelineFactory $pipelineFactory
    ) {
        $this->resourcePath = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::GENERATED)
            . DIRECTORY_SEPARATOR
            . 'code'
            . DIRECTORY_SEPARATOR
            . 'Magewirephp'
            . DIRECTORY_SEPARATOR
            . 'Magewire'
            . DIRECTORY_SEPARATOR
            . 'views';
    }

    /**
     * Compile the view at the given path.
     */
    final public function compile(string $path): string
    {
        $contents = $this->compileString($this->filesystem()->get($path));

        $this->filesystem()->ensureDirectoryExists($path = $this->generateFilePath($path));
        $this->filesystem()->put($path, $contents);

        return $path;
    }

    public function manager(): CompileManager
    {
        return $this->manager;
    }

    public function utils(): CompilerUtils
    {
        return $this->utils;
    }

    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function precompiler(): Pipeline
    {
        return $this->precompiler ??= $this->pipelineFactory->create();
    }

    public function optimizer(): Pipeline
    {
        return $this->optimizer ??= $this->pipelineFactory->create();
    }

    /**
     * Get the path to the compiled version of a view.
     */
    public function generateFilePath(string $path, bool $includeResourceDir = true): string
    {
        $path = sha1($path) . '.phtml';

        return $includeResourceDir ? $this->resourcePath . DIRECTORY_SEPARATOR . $path : $path;
    }

    /**
     * Determine if the view at the given path is expired.
     *
     * @param string $path original file path.
     */
    public function requiresCompilation(string $path): bool
    {
        $compiled = $this->generateFilePath($path);

        if ($this->filesystem()->exists($compiled)) {
            return $this->filesystem()->lastModified($path) >= $this->filesystem()->lastModified($compiled);
        }

        return true;
    }

    public function canCompile(bool $choice = null): bool|static
    {
        if ($choice) {
            $this->compile = $choice;
            return $this;
        }

        return $this->compile;
    }

    /**
     * Compile the given template contents.
     */
    protected function compileString(string $value): string
    {
        $result = '';

        // Try to run the precompile pipeline.
        $value = $this->precompiler()->run($value);

        /*
         * Iterate through all tokens returned by the Zend lexer, parsing each into valid PHP code.
         * The result will be a fully rendered PHP template, ready for native execution.
         */
        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }

        // Try to run the optimizer pipeline and return the final result.
        return $this->optimizer()->run($result);
    }

    /**
     * Parse the tokens from the template.
     */
    protected function parseToken(array $token): string
    {
        [$id, $content] = $token;

        if ($id == T_INLINE_HTML) {
            foreach ($this->compilers as $type) {
                if (is_string($type) && method_exists($this, 'compile' . $type)) {
                    $content = $this->{"compile{$type}"}($content);
                }
            }
        }

        return $content;
    }
}
