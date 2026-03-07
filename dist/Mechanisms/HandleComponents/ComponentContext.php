<?php
/**
 * Livewire copyright © Caleb Porzio (https://github.com/livewire/livewire).
 * Magewire copyright © Willem Poortman 2024-present.
 * All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */
namespace Magewirephp\Magewire\Mechanisms\HandleComponents;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\AbstractBlock;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Effects;
use Magewirephp\Magewire\Mechanisms\HandleComponents\ComponentContext\Memo;
use Magewirephp\Magewire\Component;
use AllowDynamicProperties;
#[AllowDynamicProperties]
class ComponentContext
{
    public $effects = [];
    public $memo = [];
    public function __construct(public $block, public $component, public $mounting = false, $effects = null, $memo = null)
    {
        $this->effects = $effects instanceof Effects ? $effects : null ?? ObjectManager::getInstance()->create(Effects::class);
        $this->memo = $memo instanceof Memo ? $memo : null ?? ObjectManager::getInstance()->create(Memo::class);
    }
    public function isMounting()
    {
        return $this->mounting;
    }
    public function addEffect($key, $value)
    {
        $this->getEffects()->setData($key, $value);
    }
    public function pushEffect($key, $value, $iKey = null)
    {
        $effects = $this->getEffects()->getData();
        if (!is_array($effects)) {
            $effects = [];
        }
        if ($iKey) {
            $effects[$key][$iKey] = $value;
        } else {
            $effects[$key][] = $value;
        }
        $this->getEffects()->setData($effects);
        return $this;
    }
    public function addMemo($key, $value)
    {
        $this->getMemo()->setData($key, $value);
    }
    public function pushMemo($key, $value, $iKey = null)
    {
        $memo = $this->getMemo()->getData();
        if (!is_array($memo)) {
            $memo = [];
        }
        if ($iKey) {
            $memo[$key][$iKey] = $value;
        } else {
            $memo[$key][] = $value;
        }
        $this->getMemo()->setData($memo);
        return $this;
    }
    public function setEffects(Effects $effects)
    {
        $this->effects = $effects;
        return $this;
    }
    public function setMemo(Memo $memo)
    {
        $this->memo = $memo;
        return $this;
    }
    public function getEffects(): Effects
    {
        return $this->effects;
    }
    public function getMemo(): Memo
    {
        return $this->memo;
    }
    public function getComponent(): Component
    {
        return $this->component;
    }
    public function getBlock(): AbstractBlock
    {
        return $this->block;
    }
    public function hasEffect($key, $iKey = null): bool
    {
        $has = $this->getEffects()->hasData($key);
        if ($has && $iKey) {
            $data = $this->getEffects()->getData($key);
            if (is_array($data)) {
                return isset($data[$iKey]);
            }
            return false;
        }
        return $has;
    }
    public function hasMemo($key, $iKey = null)
    {
        $has = $this->getMemo()->hasData($key);
        if ($has && $iKey) {
            $data = $this->getMemo()->getData($key);
            if (is_array($data)) {
                return isset($data[$iKey]);
            }
            return false;
        }
        return $has;
    }
}