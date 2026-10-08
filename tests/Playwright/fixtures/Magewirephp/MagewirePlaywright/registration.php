<?php

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

// Installed explicitly in the test store; never registered by Magewire's autoloader.
ComponentRegistrar::register(ComponentRegistrar::MODULE, 'Magewirephp_MagewirePlaywright', __DIR__);
