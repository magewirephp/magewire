<?php
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

declare(strict_types=1);

namespace Magewirephp\MagewireCompatibilityWithHyva\Magewire\Features\SupportHyvaCheckoutBackwardsCompatibility;

use Magewirephp\Magento\Framework\View\RenderLifecycleManager;
use Magewirephp\Magewire\ComponentHook;
use Magewirephp\Magewire\Features\SupportMagewireBackwardsCompatibility\HandleBackwardsCompatibility;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext;
use Magewirephp\Magewire\Support\AttributesReader;
use Psr\Log\LoggerInterface;
use ReflectionException;
use function Magewirephp\Magewire\store;

class SupportHyvaCheckoutBackwardsCompatibility extends ComponentHook
{
    public function __construct(
        private readonly RenderLifecycleManager $renderLifecycleManager,
        private readonly LoggerInterface $logger
    ) {
        
    }

    public function hydrate($memo): void
    {
        if (! isset($memo['bc']['enabled'])) {
            return;
        }

        store($this->component)->set('bc.enabled', $memo['bc']['enabled']);
    }

    public function dehydrate(ComponentContext $context): void
    {
        // For backwards compatibility, if an evaluation effect is present, push it into the 'serverMemo'
        // under the 'bc' key so older clients that rely on this memo structure continue to work correctly.
        $bcEffect = $context->getEffects()->getData('bc') ?? [];
        $evaluationEffect = $context->getEffects()->getData('evaluation');

        if (is_array($evaluationEffect)) {
            $bcEffect['serverMemo']['evaluation'] = $evaluationEffect;
            $context->pushEffect('bc', $bcEffect['serverMemo'], 'serverMemo');
        }

        try {
            $within = AttributesReader::for($this->component())->first(HandleBackwardsCompatibility::class);

            if ($within instanceof HandleBackwardsCompatibility) {
                $within = $within->isEnabled();
            }

            $within ??= $this->component ? store($this->component)->get('bc.enabled') : null;
            $within ??= $this->renderLifecycleManager->isWithin('hyva-checkout-main');
        } catch (ReflectionException $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }

        $context->pushMemo('bc', $within ?? false, 'enabled');
    }
}
