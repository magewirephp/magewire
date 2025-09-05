<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Features\SupportStreaming;

use Magewirephp\Magewire\ComponentHook;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\StreamedResponseFactory;
class SupportStreaming extends ComponentHook
{
    protected StreamedResponse|null $response = null;
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
        $this->response = $this->streamedResponseFactory->create(['callback' => null, 'status' => 200, 'headers' => ['Cache-Control' => 'no-cache', 'Content-Type' => 'text/event-stream', 'X-Accel-Buffering' => 'no', 'X-Livewire-Stream' => true]]);
        $this->response->sendHeaders();
    }
    public function streamContent($body): void
    {
        echo json_encode(['stream' => true, 'body' => $body, 'endStream' => true]);
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
    public function __construct(private readonly StreamedResponseFactory $streamedResponseFactory)
    {
        //
    }
}