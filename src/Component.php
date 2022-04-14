<?php declare(strict_types=1);
/**
 * Copyright © Willem Poortman 2021-present. All rights reserved.
 *
 * Please read the README and LICENSE files for more
 * details on copyrights and license information.
 */

namespace Magewirephp\Magewire;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\Element\Template;
use Magewirephp\Magewire\Model\Concern\BrowserEvent as BrowserEventConcern;
use Magewirephp\Magewire\Model\Concern\Children as ChildrenConcern;
use Magewirephp\Magewire\Model\Concern\Conversation as ConversationConcern;
use Magewirephp\Magewire\Model\Concern\Emit as EmitConcern;
use Magewirephp\Magewire\Model\Concern\Error as ErrorConcern;
use Magewirephp\Magewire\Model\Concern\Event as EventConcern;
use Magewirephp\Magewire\Model\Concern\FlashMessage as FlashMessageConcern;
use Magewirephp\Magewire\Model\Concern\Method as MethodConcern;
use Magewirephp\Magewire\Model\Concern\QueryString as QueryStringConcern;
use Magewirephp\Magewire\Model\Concern\Redirect as RedirectConcern;
use Magewirephp\Magewire\Model\Concern\View as ViewConcern;
use ReflectionClass;

/**
 * @method void boot()
 * @method void mount()
 * @method void booted()
 * @method void hydrate()
 * @method void dehydrate()
 * @method mixed updating(mixed $value, string $name)
 * @method mixed updated(mixed $value, string $name)
 */
abstract class Component implements ArgumentInterface
{
    /*
     * Still a proof of concept to separate all logic
     * and make it a bit more clear and clean.
     */
    use BrowserEventConcern,
        ChildrenConcern,
        ConversationConcern,
        EmitConcern,
        ErrorConcern,
        EventConcern,
        FlashMessageConcern,
        MethodConcern,
        QueryStringConcern,
        RedirectConcern,
        ViewConcern;

    public const LAYOUT_ITEM_TYPE = 'type';
    public const RESERVED_PROPERTIES = ['id', 'name'];
    public const COMPONENT_TYPE = 'default';

    /**
     * Component id.
     *
     * @reserved
     * @var string|null
     */
    public $id;

    /**
     * Component name.
     *
     * @reserved
     * @var string|null
     */
    public $name;

    /**
     * Layout block object.
     *
     * @var Template|null
     */
    private $parent;

    /**
     * @var array<string, mixed>|null
     */
    private $publicProperties;

    /**
     * Assign/overwrite multiple public class properties at once.
     *
     * @param array $assignees
     * @return $this
     */
    public function fill(array $assignees): self
    {
        $properties = $this->getPublicProperties();

        foreach ($assignees as $assignee => $value) {
            if (array_key_exists($assignee, $properties)) {
                $this->{$assignee} = $value;
            }
        }

        return $this;
    }

    /**
     * Reset public properties based on a fresh instance.
     *
     * @param array|null $specific
     * @param bool $boot
     * @return $this
     */
    public function reset(array $specific = null, bool $boot = false): self
    {
        $properties = array_diff($specific ?? array_keys($this->getPublicProperties()), self::RESERVED_PROPERTIES);
        $instance = ObjectManager::getInstance()->create(static::class);

        /** @var object|array $data */
        $data = $this->getParent()->getData('magewire');

        if (is_array($data)) {
            unset($data['type']);
        }
        if ($boot) {
            $instance->boot();
        }

        foreach ($properties as $property) {
            if (is_array($data)) {
                $this->{$property} = $data[$property] ?? $instance->{$property};
            } else {
                $this->{$property} = $instance->{$property};
            }
        }

        if ($boot) {
            $instance->booted();
        }
        return $this;
    }

    /**
     * @return Template|null
     */
    public function getParent(): ?Template
    {
        return $this->parent;
    }

    /**
     * @param Template $parent
     * @return $this
     */
    public function setParent(Template $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * Get a (optional cached) array with all available
     * (non-static) public property objects.
     *
     * @param bool $refresh
     * @return array
     */
    public function getPublicProperties(bool $refresh = false): array
    {
        if (($refresh ? null : $this->publicProperties) === null) {
            $properties = array_filter((new ReflectionClass($this))->getProperties(), static function ($property) {
                return $property->isPublic() && !$property->isStatic();
            });

            $data = [];

            foreach ($properties as $property) {
                $data[$property->getName()] = $property->getValue($this);
            }

            $this->publicProperties = array_diff_key($data, array_flip(self::RESERVED_PROPERTIES));
        }

        return $this->publicProperties;
    }

    /**
     * @param string $method
     * @param array $args
     * @return array|bool|mixed|null|void
     * @throws LocalizedException
     */
    public function __call(string $method, array $args)
    {
        if ($this->ignoreCall($method)) {
            return;
        }

        $key = lcfirst(substr($method, 3));

        switch (substr($method, 0, 3)) {
            case 'get':
                if (property_exists($this, $key)) {
                    return $this->{$key};
                }
                break;
            case 'has':
                return property_exists($this, $key) && $this->{$key} !== null;
            default:
                throw new LocalizedException(__('Invalid method %1::%2', [get_class($this), $method]));
        }
    }

    /**
     * @param string $method
     * @return bool
     */
    public function ignoreCall(string $method): bool
    {
        if (in_array($method, ['boot', 'mount', 'booted', 'hydrate', 'dehydrate', 'updating', 'updated'])) {
            return true;
        }

        foreach (['hydrate', 'dehydrate', 'updating', 'updated'] as $start) {
            if (strncmp($method, $start, strlen($start)) === 0) {
                return true;
            }
        }

        return false;
    }
}
