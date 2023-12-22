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
use Magento\Customer\Model\Customer;
use Magewirephp\Magewire\Model\Storage\StorageDriver;

class File
{
    protected StorageDriver $storage;
    protected string $name;

    private ?string $relativePath = null;

    public function __construct(
        StorageDriver $storage,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        string $name
    ) {
        $this->storage = $storage;
        $this->name = $name;
    }

    public function getRelativePath(): ?string
    {
        return $this->relativePath;
    }

    /**
     * Retrieve a public URL of the latest uploaded file.
     */
    public function getUrl(): ?string
    {
        if ($this->relativePath) {
            return $this->storage->getUrl($this->relativePath);
        }

        return null;
    }

    /**
     * Store the uploaded file in the given directory.
     */
    public function store(): self
    {
        $store = $this->storage->publish([$this->name], null);
        $this->relativePath = $store[0];

        return $this;
    }

    /**
     * Store in the given directory with the given filename.
     */
    public function storeAs(string $filename): self
    {
        $store = $this->storage->publish([$this->name], null, $filename);
        $this->relativePath = $store[0];

        return $this;
    }

    /**
     * Store in the given directory, with "public" visibility.
     */
    public function storePublicly(string $directory = null): self
    {
        $store = $this->storage->publish([$this->name], $directory);
        $this->relativePath = $store[0];

        return $this;
    }

    /**
     * Store in the given directory with the given filename, with "public" visibility.
     */
    public function storePubliclyAs(string $filename, string $directory = null): self
    {
        $store = $this->storage->publish([$this->name], $directory, $filename);
        $this->relativePath = $store[0];

        return $this;
    }

    /**
     * Store in the given directory, for the given customer.
     */
    public function storeForCustomer(Customer $customer, string $directory = null): self
    {
        return $this->storePublicly($directory); // WIP
    }

    /**
     * Store in the given directory with the given filename, for the given customer.
     */
    public function storeAsForCustomer(Customer $customer, string $filename, string $directory = null): self
    {
        return $this->storePubliclyAs($filename, $directory); // WIP
    }
}
