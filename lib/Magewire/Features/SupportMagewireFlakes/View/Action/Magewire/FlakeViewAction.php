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
use Magewirephp\Magewire\Features\SupportMagewireCompiling\View\ViewAction as ViewAction;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Component\FlakeFactory;
use Magewirephp\Magewire\Features\SupportMagewireFlakes\Mechanisms\ResolveComponent\ComponentResolver\FlakeResolver;
use Magewirephp\Magewire\Support\DataCollection;
use Magewirephp\Magewire\Support\DataArrayFactory;
use RuntimeException;

class FlakeViewAction extends ViewAction
{
    public function __construct(
        private readonly FlakeFactory $flakeFactory,
        private readonly DataArrayFactory $attributesFactory
    ) {
        //
    }

    /**
     * @throws RuntimeException
     */
    public function create(
        string $flake,
        array $data = [],
        array $metadata = [],
        array $variables = []
    ): AbstractBlock {
        $data = $this->attributesFactory->create()->fill($data);

        $data->each(function (DataArray $array, $value, $key) use ($variables) {
                if (str_starts_with($value, '$')) {
                    $value = trim($value, '$');

                    if (array_key_exists($value, $variables)) {
                        $array->put($key, $variables[$value]);
                    }
                }
            });

        $data  = $data->all();
        $block = $this->flakeFactory->createByName($flake, $data);

        if (! $block) {
            throw new RuntimeException('Flake block can not be found.'); // @todo
        }

        $block->setData('magewire:alias', $flake);

        $block->setNameInLayout($data['magewire:name']);
        return $block;
    }
}
