<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\Magewire\Model\View;

use BadMethodCallException;
use Magento\Framework\App\ObjectManager;
use Magewirephp\Magewire\Model\View\Utils\Alpine as AlpineViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Application as ApplicationViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Csp as CspViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Environment as EnvironmentViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Layout as LayoutViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Magewire as MagewireViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Security as SecurityViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Tailwind as TailwindViewUtil;
use Magewirephp\Magewire\Model\View\Utils\Template as TemplateViewUtil;

class Utils
{
    /**
     * @template T of UtilsInterface
     * @param array<string, T> $utilities
     */
    public function __construct(
        private readonly AlpineViewUtil $alpine,
        private readonly ApplicationViewUtil $application,
        private readonly EnvironmentViewUtil $environment,
        private readonly LayoutViewUtil $layout,
        private readonly MagewireViewUtil $magewire,
        private readonly SecurityViewUtil $security,
        private readonly TailwindViewUtil $tailwind,
        private readonly TemplateViewUtil $template,
        private readonly CspViewUtil $csp,
        private readonly array $utilities = []
    ) {
        //
    }

    public function alpinejs(): AlpineViewUtil
    {
        return $this->alpine;
    }

    public function application(): ApplicationViewUtil
    {
        return $this->application;
    }

    public function env(): EnvironmentViewUtil
    {
        return $this->environment;
    }

    public function layout(): LayoutViewUtil
    {
        return $this->layout;
    }

    public function magewire(): MagewireViewUtil
    {
        return $this->magewire;
    }

    public function security(): SecurityViewUtil
    {
        return $this->security;
    }

    public function tailwind(): TailwindViewUtil
    {
        return $this->tailwind;
    }

    public function template(): TemplateViewUtil
    {
        return $this->template;
    }

    public function csp(): CspViewUtil
    {
        return $this->csp;
    }

    /**
     * @throws BadMethodCallException
     */
    public function __call(string $utility, array $arguments = []): UtilsInterface
    {
        if (array_key_exists($utility, $this->utilities)) {
            $subject = $this->utilities[$utility];

            if ($subject instanceof UtilsInterface) {
                if (count($arguments) === 0) {
                    return $subject;
                }

                return ObjectManager::getInstance()->create($subject::class, $arguments);
            }
        }

        throw new BadMethodCallException(
            sprintf('Invalid utility "%s"', $utility)
        );
    }
}
