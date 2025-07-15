<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Features\SupportMagewireFlakes\View\Action\Magewire;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Exceptions\ComponentNotFoundException;
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction as ViewAction;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver\FlakeResolver;

class Flake extends ViewAction
{
    public function __construct(
        private readonly FlakeResolver $flakeResolver
    ) {
        //
    }

    /**
     * @throws LocalizedException
     * @throws ComponentNotFoundException
     */
    public function create(
        string $flake,
        string $content,
        array $data = [],
        array $attributes = []
    ): string {
        $block = $this->flakeResolver->make($flake);

        if ($block instanceof AbstractBlock) {
            $data['magewire:alias'] = $flake;
            $block->setNameInLayout($data['magewire:name']);

            $block->addData($data);
            return $block->toHtml();
        }

        return ''; // TBD
    }
}
