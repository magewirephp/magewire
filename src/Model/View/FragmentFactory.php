<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use InvalidArgumentException;
use LogicException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Model\View\Fragment\Html;
use Magewirephp\Magewire\Model\View\Fragment\Javascript;
use Magewirephp\Magewire\Model\View\Fragment\Script;
use Magewirephp\Magewire\Model\View\Fragment\Style;
use Magewirephp\Magewire\Model\View\Fragment\Template;
use Magewirephp\Magewire\Support\Factory;

class FragmentFactory
{
    /**
     * @param array<string, class-string<Fragment> $types
     */
    public function __construct(
        private FragmentElementFactory $elementFactory,
        private array $types = []
    ) {
        //
    }

    public function html(): Html
    {
        return $this->create(Html::class);
    }

    public function javascript(): Script
    {
        return $this->create(Javascript::class);
    }

    public function script(): Script
    {
        return $this->create(Script::class);
    }

    public function style(): Style
    {
        return $this->create(Style::class);
    }

    public function elements(): FragmentElementFactory
    {
        return $this->elementFactory;
    }

    public function template(AbstractBlock $block): Template
    {
        return $this->create(Template::class, ['block' => $block]);
    }

    /**
     * @template T of Fragment
     * @param string $name
     * @return T
     * @throws InvalidArgumentException
     * @phpstan-return T
     */
    public function custom(string $name, array $arguments = []): Fragment
    {
        if (array_key_exists($name, $this->types)) {
            return $this->create($this->types[$name], $arguments);
        }
        if (class_exists($name)) {
            return $this->create($name, $arguments);
        }

        throw new InvalidArgumentException(sprintf('Unknown fragment type "%s".', $name));
    }

    /**
     * @template T of Fragment
     * @param class-string<T> $type
     * @return T
     * @throws LogicException
     */
    private function create(string $type, array $arguments = []): Fragment
    {
        $fragment = Factory::create($type, $arguments);

        if ($fragment instanceof Fragment) {
            return $fragment;
        }

        throw new LogicException(sprintf(
            'Class "%s" does not implement Fragment interface. Expected Fragment, got %s.',
            $type,
            get_debug_type($fragment)
        ));
    }
}
