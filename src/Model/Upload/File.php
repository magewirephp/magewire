<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Livewire\FileUploadConfiguration;
use Magewirephp\Magewire\Model\Storage\StorageDriverInterface;

class File
{
    protected StorageDriverInterface $storage;
    protected string $name;

    private ?string $path = null;

    public function __construct(
        StorageDriverInterface $storage,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        string $name
    ) {
        $this->storage = $storage;
        $this->name = $name;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function getUrl(): string
    {
        return '';
    }

    public function store()
    {
        $store = $this->storage->store([$this->name]);
        $this->path = $store[0];
    }
}
