<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Model\Action\SyncInput;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Upload
{
    protected UploadAdapterInterface $uploadAdapter;
    protected SyncInput $syncInput;

    /**
     * @param UploadAdapterInterface $uploadAdapter
     * @param SyncInput $syncInput
     */
    public function __construct(
        UploadAdapterInterface $uploadAdapter,
        SyncInput $syncInput
    ) {
        $this->uploadAdapter = $uploadAdapter;
        $this->syncInput = $syncInput;
    }

    public function startUpload(string $property, $file, $isMultiple, Component $component)
    {
        // Maybe check if the property exists... or that is done elsewhere (DUNNO)

        $component->emit(
            $this->uploadAdapter->getGenerateSignedUploadUrlEvent(),
            $property,
            $this->uploadAdapter->generateSignedUploadUrl($file, $isMultiple)
        )->self();
    }

    public function finishUpload(string $property, $tmpPath, $isMultiple, Component $component)
    {
        if ($isMultiple) {
//            $file = collect($tmpPath)->map(function ($i) {
//                return TemporaryUploadedFile::createFromLivewire($i);
//            })->toArray();
//            $component->emit('upload:finished', $name, collect($file)->map->getFilename()->toArray())->self();
        } else {
            $component->emit('upload:finished', $property, [$tmpPath[0]])->self();

//            // If the property is an array, but the upload ISNT set to "multiple"
//            // then APPEND the upload to the array, rather than replacing it.
//            if (is_array($value = $this->getPropertyValue($name))) {
//                $file = array_merge($value, [$file]);
//            }
        }

        $this->syncInput->handle($component, ['name' => $property, 'value' => $tmpPath[0]]);
    }
}
