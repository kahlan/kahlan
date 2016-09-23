## Integration with popular frameworks

Kahlan relies on the Composer autoloader. As such, it is compatible with most frameworks. However a couple popular frameworks use their own autoloader, so you will need to add your namespaces to be autoloaded correctly in the test environment.

You will need to configure your Kahlan config file to manually add to the Composer autoloader which are "outside the composer scope".

### Working with a PSR-0 compatible architecture.

Let's take a situation where you have the following directories: `app/models/` and  `app/controllers/` and each one are respectively attached to the `Api\Models` and `Api\Controllers` namespaces. To autoload them with Kahlan you will need to manually add these PSR-4 namespaces in your **kahlan-config.php** config file:

```php
use Kahlan\Filter\Filter;

Filter::register('mycustom.namespaces', function($chain) {

  $this->autoloader()->addPsr4('Api\\Models\\', __DIR__ . '/app/models/');
  $this->autoloader()->addPsr4('Api\\Controllers\\', __DIR__ . '/app/controllers/');
  return $chain->next();

});

Filter::apply($this, 'namespaces', 'mycustom.namespaces');
```

### Laravel

To import all Laravel "test facilities" into Kahlan you can make use of [this dedicated plugin](https://github.com/jarektkaczyk/laravel-kahlan).

### Phalcon

When a class extends a built-in class (i.e. a non PHP class) it's not possible to stub parent class methods since they are not in PHP userland.

Long story short, let's take the following example as an illustration:

We have a model:

```php
namespace Api\Models;

class MyModel extends \Phalcon\Mvc\Model
{
}
```

And we want `findFirst()` to return a stubbed result. This can be translated into the following spec:

```php
namespace Api\Spec\Contollers;

use Api\Models\MyModel;

describe("MyModel", function() {

    it("stubs findFirst as an example", function() {

        $article = new MyModel();

        allow('Api\Models\MyModel')->toReceive('::findFirst')->andReturn($article);

        $actual = MyModel::findFirst();

        expect($actual)->toBe($article);

    });

});
```

Unfortunately it doesn't work out of the box because `MyModel` extends `Phalcon\Mvc\Model` which is a core class (i.e a class compiled from C sources). So `MyModel::findFirst()` doesn't exist in PHP userland and can't be stubbed.

The workaround here is to add the Kahlan's `Layer` patcher in [your `kahlan-config.php` file](config-file.md). The `Layer` patcher will dynamically replace all `extends` done on core class to an intermediate layer class in PHP.

So for our example above, the `Layer` patcher can be configured like the following in [your `kahlan-config.php` file](config-file.md):

```php
use Kahlan\Filter\Filter;
use Kahlan\Jit\Interceptor;
use Kahlan\Plugin\Layer;

Filter::register('api.patchers', function($chain) {
    $interceptor = Interceptor::instance();
    $patchers = $interceptor->patchers();
    $patchers->add('layer', new Layer([
        'override' => [
            'Phalcon\Mvc\Model' // apply a layer on top of all classes extending `Phalcon\Mvc\Model`.
        ]
    ]));

    return $chain->next();
});

Filter::apply($this, 'patchers', 'api.patchers');
```

**Note:** You will probably need to remove all cached files using `kahlan --cc`.

### Working with a custom autoloader not compatible with PSR-0.

In this case your must implement a `PSR-0` **Composer** compatible autoloader. To have a right direction you could see at [sources](https://github.com/composer/composer/blob/master/src/Composer/Autoload/ClassLoader.php), and take care of `findFile`, `loadClass` and `add` functions.
