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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction as ViewAction;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver\FlakeResolver;

class FlakeViewAction extends ViewAction
{
    public function __construct(
        private readonly FlakeResolver $flakeResolver
    ) {
        //
    }

    /**
     * @throws LocalizedException
     */
    public function create(
        string $flake,
        array $data = [],
        array $metadata = []
    ): string {
        $block = $this->flakeResolver->make($flake, $data);

        if (! $block) {
            return '<div></div>'; // TBD
        }

        $block->setData('magewire:alias', $flake);

        // Flake metadata.
        $block->setData('magewire:flake', [
            'element' => [
                'attributes' => $metadata['attributes'] ?? []
            ]
        ]);

        $block->setNameInLayout($data['magewire:name']);

        return $block->toHtml();
    }
}
