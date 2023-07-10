<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Validation\ValidationException;

interface UploadAdapterInterface
{
    public const QUERY_PARAM_EXPIRES = 'expires';
    public const QUERY_PARAM_SIGNATURE = 'signature';
    public const QUERY_PARAM_ADAPTER = 'adapter';

    /**
     * Create a temporary signed route URL.
     */
    public function generateSignedUploadUrl(array $file, bool $isMultiple): string;

    public function getGenerateSignedUploadUrlEvent(): string;

    /**
     * @deprecated don't use until the deprecated sign has been removed (WIP).
     */
    public function getDriver(): DriverInterface;

    /**
     * Get upload controller action route.
     */
    public function getRoute(): string;

    /**
     * Returns a snake cased adapter accessor.
     */
    public function getAccessor(): string;

    /**
     * Stash files temporarily (e.g. var/tmp/ directory).
     *
     * @param array $files<int, mixed>
     * @return array<int, string>
     */
    public function stash(array $files): array;

    /**
     * Store files permanently.
     *
     * @return array<int, string|null>
     */
    public function store(array $paths, string $directory = null): array;
}
