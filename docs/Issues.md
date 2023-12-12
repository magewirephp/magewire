# Magewire - Issues

> Repository issues have been temporarily disabled to encourage developers to submit pull requests instead.
> Magewire operates as an open-source, community-driven package, granting exclusive privileges for emergency fixes or
> feature requests to sponsors. Alternatively, the discussions panel is accessible to everyone, providing a platform to
> initiate conversations on specific topics.

## Common issues

A list of the most common issues developers run into during development. Feel free to open a pull request if you miss
anything.

### 1. Magewire security vulnerability error message

Sometimes during the development of a component you're getting the following error message

`{"message":"Magewire security vulnerability:
Magewire encountered corrupt data\n when trying to hydrate the Class\\Namespace\\Here component.
Ensure that the [name, id, resolver and data] of the Magewire component wasn't\n tampered with between requests.","code":500}`

Typically, this issue arises when the Magewire object is transmitted to a child block using the magewire data key.
By doing so, a parent Magewire component is bound to another block as the magewire argument, prompting Magewire to
attempt processing.

```php
<div>
    $block->getChild('child-alias-here')->setData('magewire', $magewire)->toHtml();
</div>
```

Instead, opt for an alternative data key.

```php
<div>
    $block->getChild('child-alias-here')->setData('magewireParent', $magewire)->toHtml();
</div>
```
