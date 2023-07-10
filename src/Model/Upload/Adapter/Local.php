<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload\Adapter;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Math\Random;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Magewirephp\Magewire\Model\Upload\AbstractAdapter;
use Magewirephp\Magewire\Model\Upload\TemporaryFileFactory;

class Local extends AbstractAdapter
{
    public const ACCESSOR = 'local';

    protected Filesystem $fileSystem;
    protected Random $randomizer;
    protected TemporaryFileFactory $temporaryFileFactory;

    public function __construct(
        DateTime $dateTime,
        SecurityHelper $securityHelper,
        FileDriver $fileDriver,
        RequestInterface $request,
        Filesystem $fileSystem,
        Random $randomizer,
        TemporaryFileFactory $temporaryFileFactory
    ) {
        parent::__construct($dateTime, $securityHelper, $fileDriver, $request);

        $this->fileSystem = $fileSystem;
        $this->randomizer = $randomizer;
        $this->temporaryFileFactory = $temporaryFileFactory;
    }

    /**
     * @param array<TemporaryUploader> $files
     * @throws FileSystemException
     * @throws Exception
     */
    public function stash(array $files): array
    {
        $paths = [];

        foreach (array_keys($files) as $file) {
            $temporaryFiles[] = $this->temporaryFileFactory->create(['fileId' => 'files[' . $file . ']']);
        }

        foreach ($temporaryFiles ?? [] as $file) {
            $fileDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
            $name = $file->generateHashNameWithOriginalNameEmbedded();

            $file->setAllowCreateFolders(false);
            $file->setAllowRenameFiles(true);
            $file->setFilenamesCaseSensitivity(false);

            /* @todo still needs to be caught somewhere. */
            $file->validateFile();

            $file->save($fileDirectory->getAbsolutePath('magewire'), $name);
            $paths[] = $file->getUploadedFileName();
        }

        return $paths;
    }

    /**
     * @throws FileSystemException
     */
    public function store(array $paths, string $directory = null): array
    {
        $sourceDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $targetDirectory = $this->fileSystem->getDirectoryWrite(DirectoryList::UPLOAD);

        return array_map(function($tmp) use ($sourceDirectory, $targetDirectory) {
            $path = 'magewire' . DIRECTORY_SEPARATOR . $tmp;

            if ($sourceDirectory->isFile($path) && $sourceDirectory->copyFile($path, $path, $targetDirectory)) {
                return $targetDirectory->getRelativePath($path);
            }

            return null;
        }, $paths);
    }
}
