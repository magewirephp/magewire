<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Hyva\CheckoutCore\Model\Magewire\UpdateAdapterInterface;
use Magewirephp\Magewire\Exception\NoSuchUploadAdapterInterface;

class AdapterProvider
{
    /** @var array<string, UpdateAdapterInterface> */
    protected array $adapters;

    /**
     * @param array<string, UploadAdapterInterface> $adapters
     */
    public function __construct(
        array $adapters = []
    ) {
        $this->adapters = array_filter($adapters, static function (UploadAdapterInterface $adapter, $name) {
            return $name === $adapter->getName();
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @throws NoSuchUploadAdapterInterface
     */
    public function getByName(string $adapter): UploadAdapterInterface
    {
        $adapter = $this->adapters[$adapter];

        if ($adapter) {
            return $adapter;
        }

        throw new NoSuchUploadAdapterInterface();
    }
}
