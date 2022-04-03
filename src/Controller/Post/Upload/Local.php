<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Controller\Post\Upload;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magewirephp\Magewire\Helper\Security as SecurityHelper;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class Local implements HttpPostActionInterface, CsrfAwareActionInterface
{
    protected JsonFactory $resultJsonFactory;
    protected RequestInterface $request;
    protected SecurityHelper $securityHelper;
    protected Filesystem $fileSystem;
    protected FileFactory $fileFactory;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param RequestInterface $request
     * @param SecurityHelper $securityHelper
     * @param Filesystem $fileSystem
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        RequestInterface $request,
        SecurityHelper $securityHelper,
        Filesystem $fileSystem
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->request = $request;
        $this->securityHelper = $securityHelper;
        $this->fileSystem = $fileSystem;
    }

    /**
     * @throws FileSystemException
     */
    public function execute(): Json
    {
        // CLEAN UP THE TMP DIR FIRST...
        $result = $this->resultJsonFactory->create();

        if ($this->securityHelper->validateRouteSignature($this->request) === false) {
            throw new HttpException(401, 'We could not upload the file duo to security reasons');
        }

        // Upload the file and return the name of the file.
        $filename = md5((string) random_int(1111, 9999)) . '.jpg';
        $path = 'magewire/' . $filename;

        $directory = $this->fileSystem->getDirectoryWrite(DirectoryList::TMP);
        $directory->writeFile($directory->getAbsolutePath($path), file_get_contents($_FILES['files']['tmp_name'][0]));

        return $result->setData(['paths' => [$filename]]);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
