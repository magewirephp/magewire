<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

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

    public function validate(array $rules = [], array $messages = [], array $data = null, array $aliases = [], bool $mergeWithClassProperties = true): bool
    {
        foreach ($data ?? $this->getPublicProperties(true) as &$value) {
            // WIP
        }

        return parent::validate($rules, $messages, $data, $aliases, $mergeWithClassProperties);
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

//        if (is_null($errorsInJson)) {
//            // Handle any translations/custom names
//            $translator = app()->make('translator');
//
//            $attribute = $translator->get("validation.attributes.{$name}");
//            if ($attribute === "validation.attributes.{$name}") $attribute = $name;
//
//            $message = trans('validation.uploaded', ['attribute' => $attribute]);
//            if ($message === 'validation.uploaded') $message = "The {$name} failed to upload.";
//
//            throw ValidationException::withMessages([$name => $message]);
//        }
//
//        $errorsInJson = $isMultiple
//            ? str_ireplace('files', $name, $errorsInJson)
//            : str_ireplace('files.0', $name, $errorsInJson);
//
//        $errors = json_decode($errorsInJson, true)['errors'];
//
//        throw (ValidationException::withMessages($errors));
    }

    public function removeUpload($name, $tmpFilename)
    {
//        $uploads = $this->getPropertyValue($name);
//
//        if (is_array($uploads) && isset($uploads[0]) && $uploads[0] instanceof TemporaryUploadedFile) {
//            $this->emit('upload:removed', $name, $tmpFilename)->self();
//
//            $this->syncInput($name, array_values(array_filter($uploads, function ($upload) use ($tmpFilename) {
//                if ($upload->getFilename() === $tmpFilename) {
//                    $upload->delete();
//                    return false;
//                }
//
//                return true;
//            })));
//        } elseif ($uploads instanceof TemporaryUploadedFile) {
//            $uploads->delete();
//
        $this->emit('upload:removed', $name, $tmpFilename)->self();
//
//            if ($uploads->getFilename() === $tmpFilename) $this->syncInput($name, null);
//        }
    }

//    /**
//     * @throws FileSystemException
//     * @throws AcceptableException
//     */
//    public function validate(
//        array $rules = [],
//        array $messages = [],
//        array $data = null,
//        bool $mergeWithClassProperties = true
//    ): bool {
//        $data = $this->convertFilesData($data ?? $this->getPublicProperties(true));
//
//        return parent::validate(
//            $rules,
//            $messages,
//            $data,
//            $mergeWithClassProperties
//        );
//    }
//
//    /**
//     * @throws FileSystemException
//     */
//    private function convertFilesData(array $data): array
//    {
//        foreach ($data as $key => $value) {
//            if (is_string($value) && $this->temporaryFile->isTemporaryFilename($value)) {
//                $this->{$value} = $this->convertFilesData($value);
//            } elseif (is_array($value)) {
//                foreach ($value as $k => $v) {
//                    if ($this->temporaryFile->isTemporaryFilename($v)) {
//                        $this->{$v} = $this->convertFileStringToArray($v);
//                    }
//                }
//            }
//        }
//
//        return $data;
//    }
//
//    /**
//     * @throws FileSystemException
//     */
//    private function convertFileStringToArray(string $value): array
//    {
//        $fileDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::TMP);
//        $filePath = $fileDirectory->getAbsolutePath('magewire') . '/' . $value;
//
//        return [
//            'name'     => TemporaryFile::extractOriginalNameFromFilePath($value),
//            'type'     => mime_content_type($filePath),
//            'tmp_name' => $filePath,
//            'size'     => filesize($filePath),
//            'error'    => ''
//        ];
//    }
}
