<?php

namespace Magewirephp\Magewire\Features\SupportStreaming;

use Magewirephp\Magewire\ComponentHook;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\StreamedResponseFactory;

class SupportStreaming extends ComponentHook
{
    protected StreamedResponse|null $response = null;

    public function __construct(
        private readonly StreamedResponseFactory $streamedResponseFactory
    ) {
        //
    }

    public function stream($name, $content, $replace = false): void
    {
        $this->ensureStreamResponseStarted();
        $this->streamContent(['name' => $name, 'content' => $content, 'replace' => $replace]);
    }

    public function ensureStreamResponseStarted(): void
    {
        if ($this->response) {
            return;
        }

        $this->response = $this->streamedResponseFactory->create([
            'callback' => null,
            'status' => 200,
            'headers' => [
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'text/event-stream',
                'X-Accel-Buffering' => 'no',
                'X-Livewire-Stream' => true,
            ]
        ]);

        $this->response->sendHeaders();
    }

    public function streamContent($body): void
    {
        echo json_encode([
            'stream' => true,
            'body' => $body,
            'endStream' => true
        ]);

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
