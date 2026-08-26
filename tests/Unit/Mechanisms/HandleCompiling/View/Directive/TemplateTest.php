<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Mechanisms\HandleCompiling\View\Directive;

use Magewirephp\Magewire\Mechanisms\HandleCompiling\View\Directive\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function test_it_compiles_a_started_template_fragment_scope(): void
    {
        $directive = new Template();
        $opening = $directive->template();

        self::assertMatchesRegularExpression('/^<\?php \$([a-z]+) = \$__magewire->utils\(\)->fragment\(\)->make\(\)->template\(\$block\)->start\(\) \?>$/', $opening);

        preg_match('/\$([a-z]+) =/', $opening, $matches);

        self::assertSame(sprintf('<?php $%s->end() ?>', $matches[1]), $directive->endtemplate());
    }
}
