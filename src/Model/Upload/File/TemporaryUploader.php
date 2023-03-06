<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Upload\File;

class TemporaryUploader extends \Magento\MediaStorage\Model\File\Uploader
{
    public const IDENTIFIER = 'tmpfile_';

    public function generateHashNameWithOriginalNameEmbedded()
    {
        $identifier = self::IDENTIFIER;
        $hash = uniqid();
        $meta = str_replace('/', '_', '-meta' . base64_encode($this->_file['name']) . '-');
        $extension = '.' . $this->getFileExtension();

        return $identifier . $hash . $meta . $extension;
    }

    public static function isTemporaryFilename(string $filename): bool {
        return preg_match('/^' . self::IDENTIFIER . '/', $filename);
    }

    public static function extractOriginalNameFromFilePath($path)
    {
        return base64_decode(array_first(explode('-', array_last(explode('-meta', str_replace('_', '/', $path))))));
    }
}
