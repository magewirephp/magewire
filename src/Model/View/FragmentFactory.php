<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use Magento\Framework\ObjectManagerInterface;
use Magewirephp\Magewire\Model\View\Fragment\Html;
use Magewirephp\Magewire\Model\View\Fragment\Javascript;
use Magewirephp\Magewire\Model\View\Fragment\Script;
use Magewirephp\Magewire\Model\View\Fragment\Style;
use RuntimeException;

class FragmentFactory
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager
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

    /**
     * @template T of Fragment
     * @param class-string<T> $type
     * @return T
     */
    private function create(string $type): Fragment
    {
        $fragment = $this->objectManager->create($type);

        if ($fragment instanceof Fragment) {
            return $fragment;
        }

        throw new RuntimeException('Invalid fragment type.');
    }
}
