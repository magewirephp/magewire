# Magewire - Notes
> Please keep in mind that Magewire is currently in a Beta-phase. Therefore not all architectural choices are set in
> concrete. So make sure you are aware of the risks of building on top of the platform in it's current state.

## Assign Method Usage
- **Date**: 07/10/2021
- **Category**: Property Binding

So this assign method is here because we need a system where we can trigger methods like ```updating``` & ```updated```.
Thanks to the ```assign``` method, this will trigger immediately after the public property get a new value assigned.

Now, what if this was replaced with a Hydrator? It would make it a lot more simple because developers don't have to
write extra code to assign a value onto a property. In that case you would be able to just use ```$this->foo = 'bar'```.
