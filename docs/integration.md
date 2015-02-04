## Integration with popular frameworks

Kahlan fits perfectly with the composer autoloader. However a couple of popular frameworks still use their own autoloader and you will need to make all your namespaces to be autoloaded correctly in the test environment to make it works.

Hopefully It's easy and simple. Indeed almost all popular frameworks autoloaders are **PSR-0**/**PSR-4** compatible, so the only thing you will need to do is to correclty configure your **kahlan-config.php** config file to manually add to the composer autoloader all namespaces which are "ouside the composer scope".

### Phalcon

Let's take a situation where you have the following directories: `app/models/` and  `app/controllers/`. And each one are respectively attached to the `Api\Models` and `Api\Controllers` namespaces. So in this case you will need to manually add this both **PSR-4** based namespaces in your **kahlan-config.php** config file like the following:

```php
use filter\Filter;

Filter::register('mycustom.namespaces', function($chain) {

  $this->_autoloader->addPsr4('Api\\Models\\', __DIR__ . '/app/models/');
  $this->_autoloader->addPsr4('Api\\Controllers\\', __DIR__ . '/app/controllers/');

});

Filter::apply($this, 'namespaces', 'mycustom.namespaces');
```
