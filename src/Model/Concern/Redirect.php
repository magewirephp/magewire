<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire\Model\Concern;

use Magewirephp\Magewire\Model\Element\Redirect as RedirectElement;

trait Redirect
{
    /** @var RedirectElement|null $redirect */
    protected $redirect;

    /**
     * Redirect away from the current page.
     *
     * @param string $path
     * @param array $params
     * @return RedirectElement
     */
    public function redirect(string $path, array $params = []): RedirectElement
    {
        return $this->redirect = new RedirectElement($path, $params);
    }

    /**
     * @return RedirectElement|null
     */
    public function getRedirect(): ?RedirectElement
    {
        return $this->redirect;
    }
}
