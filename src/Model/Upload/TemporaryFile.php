<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Magewirephp\Magewire\Model\Storage\StorageDriver;

class TemporaryFile extends \Magento\MediaStorage\Model\File\Uploader
{
    public const IDENTIFIER = 'magewire-file';

    public function generateHashNameWithOriginalNameEmbedded(): string
    {
        $hash = uniqid();
        $meta = str_replace('/', '_', '-meta' . base64_encode($this->_file['name']) . '-');
        $extension = '.' . $this->getFileExtension();

        return $hash . $meta . $extension;
    }

    public function isTemporaryFilename(string $filename): bool
    {
        return preg_match('/^' . self::IDENTIFIER . '/', $filename);
    }

    public function extractOriginalNameFromFilePath($path)
    {
        return base64_decode(
            array_first(
                explode('-', array_last(explode('-meta', str_replace('_', '/', $path))))
            )
        );
    }
}
