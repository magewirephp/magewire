<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Upload;

use Magento\Framework\Filesystem\DriverInterface;

interface UploadAdapterInterface
{
    /**
     * Create a temporary signed route URL.
     *
     * @param array $file
     * @param bool $isMultiple
     * @return string
     */
    public function generateSignedUploadUrl(array $file, bool $isMultiple): string;

    /**
     * @return string
     */
    public function getGenerateSignedUploadUrlEvent(): string;

    /**
     * @return DriverInterface
     */
    public function getDriver(): DriverInterface;

    /**
     * @return string
     */
    public function getRoute(): string;
}
