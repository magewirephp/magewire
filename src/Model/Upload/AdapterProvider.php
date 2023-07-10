<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Magewirephp\Magewire\Exception\NoSuchUploadAdapterInterface;

class AdapterProvider
{
    /** @var array<string, UploadAdapterInterface> */
    protected array $adapters;

    public function __construct(
        array $adapters = []
    ) {
        $this->adapters = array_filter($adapters, static function (UploadAdapterInterface $adapter, $name) {
            return $name === $adapter->getAccessor();
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * @throws NoSuchUploadAdapterInterface
     */
    public function getByAccessor(string $adapter): UploadAdapterInterface
    {
        $adapter = $this->adapters[$adapter];

        if ($adapter) {
            return $adapter;
        }

        throw new NoSuchUploadAdapterInterface();
    }
}
