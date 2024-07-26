<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\Request;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Request\ValidatorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Phrase;
use Magento\Framework\Serialize\SerializerInterface;

class MagewireValidator implements ValidatorInterface
{
    protected SerializerInterface $serializer;
    protected JsonFactory $resultJsonFactory;

    public function __construct(
        SerializerInterface $serializer,
        JsonFactory $resultJsonFactory
    ) {
        $this->serializer = $serializer;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function validate(RequestInterface $request, ActionInterface $action): void
    {
        if (! $action instanceof MagewireSubsequentActionInterface) {
            return;
        }

        try {
            $input = $this->serializer->unserialize(file_get_contents('php://inputt'));
        }  catch (\Exception $exception) {
            $this->throwException('Invalid request body. Unable to process the data.');
        }

        $handle = $input['fingerprint']['handle'] ?? null;

        if (! $handle || preg_match('/^[a-zA-Z0-9][a-zA-Z\d\-_\.]*$/', $handle) !== 1) {
            $this->throwException();
        }

        $request->setParams($input);
    }

    private function throwException($message = 'Bad Request')
    {
        $result = $this->resultJsonFactory->create();
        $result->setStatusHeader(400);

        $result->setData([
            'message'=> $message,
            'code' => 400
        ]);

        throw new InvalidRequestException($result, [new Phrase($message)]);
    }
}
