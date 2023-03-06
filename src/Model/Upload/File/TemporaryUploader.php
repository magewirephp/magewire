<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\File;

use Magento\MediaStorage\Model\File\Uploader;

class TemporaryUploader extends Uploader
{
    public function generateHashNameWithOriginalNameEmbedded()
    {
        $hash = uniqid();
        $meta = str_replace('/', '_', '-meta' . base64_encode($this->_file['name']) . '-');
        $extension = '.' . $this->getFileExtension();

        return $hash . $meta . $extension;
    }

    public static function extractOriginalNameFromFilePath($path)
    {
        return base64_decode(array_first(explode('-', array_last(explode('-meta', str_replace('_', '/', $path))))));
    }
}
