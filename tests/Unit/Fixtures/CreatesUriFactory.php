<?php

declare(strict_types=1);

namespace Magewirephp\Magewire\Tests\Unit\Fixtures;

use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriFactory;
use Magento\Framework\Code\Generator\Io;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\ObjectManager\Code\Generator\Factory;
use Magento\Framework\ObjectManagerInterface;
use RuntimeException;

trait CreatesUriFactory
{
    private function uriFactory(ObjectManagerInterface $objectManager): UriFactory
    {
        // Standalone PHPUnit does not register Magento's generated-code autoloader.
        if (! class_exists(UriFactory::class)) {
            $io = new Io(new File(), sys_get_temp_dir() . '/magewire-unit-' . getmypid());
            $io->makeResultFileDirectory(UriFactory::class);
            $generator = new Factory(Uri::class, UriFactory::class, $io);
            $file = $generator->generate();

            if ($file === false) {
                throw new RuntimeException(implode("\n", $generator->getErrors()));
            }

            require $file;
        }

        return new UriFactory($objectManager);
    }
}
