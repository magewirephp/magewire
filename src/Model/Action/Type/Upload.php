<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Action\Type;

use Magewirephp\Magewire\Component;
use Magewirephp\Magewire\Exception\ComponentActionException;
use Magewirephp\Magewire\Model\Action\SyncInput;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;

class Upload
{
    protected UploadAdapterInterface $uploadAdapter;
    protected SyncInput $syncInput;

    public function __construct(
        SyncInput $syncInput
    ) {
        $this->syncInput = $syncInput;
    }

    public function startUpload(string $property, $file, $isMultiple, Component\Upload $component)
    {
        $component->emit(
            $component->getAdapter()->getGenerateSignedUploadUrlEvent(),
            $property,
            $component->getAdapter()->generateSignedUploadUrl($file, $isMultiple)
        )->self();
    }
    
    /**
     * @throws ComponentActionException
     */
    public function finishUpload(string $property, $tmpPath, $isMultiple, Component $component): void
    {
        if ($isMultiple) {
//            $file = collect($tmpPath)->map(function ($i) {
//                return TemporaryUploadedFile::createFromLivewire($i);
//            })->toArray();
//            $component->emitSelf('upload:finished', $name, collect($file)->map->getFilename()->toArray())->self();
        } else {
            $component->emit('upload:finished', $property, [$tmpPath[0]])->self();
            $this->syncInput->handle($component, ['name' => $property, 'value' => $tmpPath[0] ?? null]);
        }
    }
}
