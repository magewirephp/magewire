<?php
/**
 * Copyright © W. Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information..
 */

declare(strict_types=1);

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Magewirephp_Magewire',
    __DIR__
);

ComponentRegistrar::register(
    ComponentRegistrar::LIBRARY,
    'Magewirephp_Magewire',

    /*
     * At the time of writing, the `dist` folder was manually added as the `path` for the library registration.
     * Naturally, this is variable thanks to the  portman.config.php configuration.
     *
     * Therefore, it is important to be aware that if the `directories.output` path changes, the path here must
     * also be updated. The same applies to the fact that if the module path changes, this must also be taken
     * into account, as we assume the path in which this file is located.
     */
    __DIR__ . '/../dist'
);
