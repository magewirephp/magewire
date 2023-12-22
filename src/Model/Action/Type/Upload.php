<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Model\Action\SyncInput;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Upload
{
    protected SyncInput $syncInput;
    protected UrlInterface $urlBuilder;

    public function __construct(
        SyncInput $syncInput,
        UrlInterface $urlBuilder,
        DateTime $dateTime
    ) {
        $this->syncInput = $syncInput;
        $this->urlBuilder = $urlBuilder;
        $this->dateTime = $dateTime;
    }

    public function startUpload(string $property, $file, $isMultiple, Component\Upload $component)
    {
        $component->validate();

        $url = $this->urlBuilder->getRouteUrl('magewire/post/upload', [
            'expires' => $this->dateTime->gmtTimestamp() + 1900
        ]);

        $component->emit('upload:generatedSignedUrl', $property, $url)->self();
    }

    /**
     * @throws ComponentActionException
     */
    public function finishUpload(string $property, $temporaryPath, $isMultiple, Component $component): void
    {
        $component->emit('upload:finished', $property, [$temporaryPath[0]])->self();
        $this->syncInput->handle($component, ['name' => $property, 'value' => 'magewire-file:' . $temporaryPath[0] ?? null]);
    }
}
