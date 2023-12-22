<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Component;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magewirephp\Magewire\Exception\AcceptableException;
use Magewirephp\Magewire\Model\Storage\StorageDriver;
use Magewirephp\Magewire\Model\Upload\File;
use Magewirephp\Magewire\Model\Upload\FileFactory;
use Magewirephp\Magewire\Model\Upload\TemporaryFile;
use Magewirephp\Magewire\Model\Upload\TemporaryFileFactory;
use Magewirephp\Magewire\Model\Upload\UploadAdapterInterface;
use Rakit\Validation\Validator;

abstract class Upload extends Form
{
    public const COMPONENT_TYPE = 'file-upload';

    protected StorageDriver $storage;
    protected FileFactory $fileFactory;

    public function __construct(
        Validator $validator,
        StorageDriver $storage,
        FileFactory $fileFactory
    ) {
        $validator->setValidator('required', new \Magewirephp\Magewire\Model\Upload\Validation\Rules\Required);
        $validator->setValidator('mimes', new \Magewirephp\Magewire\Model\Upload\Validation\Rules\Mimes);
        $validator->setValidator('uploaded_file', new \Magewirephp\Magewire\Model\Upload\Validation\Rules\UploadedFile);

        parent::__construct($validator);

        $this->storage = $storage;
        $this->fileFactory = $fileFactory;
    }

    public function hydrate(): void
    {
        $properties = $this->getPublicProperties(true);

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $property => $value) {
            if (is_string($value) && substr($value, 0, strlen('magewire-file:'))) {
                // Transforms an incoming subsequent request back into a file, incorporating its chosen storage mechanism.
                $this->{$property} = $this->fileFactory->create([
                    'storage' => $this->storage,
                    'name' => ltrim($value, 'magewire-file:')
                ]);
            }
        }
    }

    public function dehydrate(): void
    {
        $properties = $this->getPublicProperties(true);

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $property => $value) {
            if (is_string($value) && substr($value, 0, strlen('magewire-file:'))) {
                $this->{$property} = ltrim($value, 'magewire-file:');
            }
        }
    }

    public function uploadErrored($name, $errorsInJson, $isMultiple)
    {
        $this->emit('upload:errored', $name)->self();
    }

    public function removeUpload($name, $tmpFilename)
    {
        $this->emit('upload:removed', $name, $tmpFilename)->self();
    }
}
