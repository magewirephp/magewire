<?php declare(strict_types=1);

namespace Magewirephp\Magewire\Helper;

use Magento\Framework\App\PageCache\Version;

class PrivateContentVersion extends Version
{
    public function update(): void
    {
        parent::process();
    }
}
