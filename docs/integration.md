## Integration with popular frameworks

Kahlan fits perfectly with the composer autoloader. However a couple of popular frameworks still use their own autoloader and you will need to make all your namespaces to be autoloaded correctly in the test environment to make it works.

Hopefully It's easy and simple. Indeed almost all popular frameworks autoloaders are **PSR-0**/**PSR-4** compatible, so the only thing you will need to do is to correctly configure your **kahlan-config.php** config file to manually add to the composer autoloader all namespaces which are "ouside the composer scope".

### Working with a PSR-0 compatible architecture.

Let's take a situation where you have the following directories: `app/models/` and  `app/controllers/` and each one are respectively attached to the `Api\Models` and `Api\Controllers` namespaces. To make them autoloaded with Kahlan you will need to manually add this **PSR-4** based namespaces in your **kahlan-config.php** config file:

```php
use filter\Filter;

Filter::register('mycustom.namespaces', function($chain) {

  $this->autoloader()->addPsr4('Api\\Models\\', __DIR__ . '/app/models/');
  $this->autoloader()->addPsr4('Api\\Controllers\\', __DIR__ . '/app/controllers/');
  return $chain->next();

});

Filter::apply($this, 'namespaces', 'mycustom.namespaces');
```

### Using the `Layer` patcher (Phalcon).

When a class extends a built-in class (i.e. a non PHP class) it's not possible to stub core methods. Long story short, let's take the following example as an illustration:

We have a model:

```php
namespace Api\Models;

class MyModel extends \Phalcon\Mvc\Model
{
    public $title;
    public function getTitle()
    {
        return $this->title;
    }
}
```

We have a controller:

```php
namespace Api\Contollers;

use Exception;
use Api\Models\MyModel;

class MyController extends \Phalcon\Mvc\Controller
{
   public function indexAction()
   {
      $article = MyModel::findFirst();
      $this->view->setVar('title', $article->getTitle());
   }
}
```

And we want to check that `indexAction()` correctly sets the `'title'` view var. This check can be tranlsated into the following spec:

```php
namespace Api\Spec\Contollers;

use Api\Models\MyModel;
use Api\Contollers\MyController;
use kahlan\plugin\Stub;

describe("MyController", function() {

    describe("->indexAction()", function() {

        it("correctly populates the view var", function() {

            $article = new MyModel();
            $article->title = 'Hello World';

            Stub::on('Api\Models\MyModel')->method('::findFirst')->andReturn($article);

            $controller = new MyController();
            $controller->indexAction();

            expect($controller->view->getVar('title'))->toBe('Hello World');

        });

    });

});
```

Unfortunalty it doesn't work out of the box. Indeed `MyModel` extends `Phalcon\Mvc\Model` which is a core class (i.e a class compiled from C sources). Since the method `MyModel::findFirst()` doesn't exists in PHP land, it can't be stubbed.

The workaround here is to configure the Kahlan's `Layer` patcher in [the `kahlan-config.php` file](config-file.md). The `Layer` patcher can dynamically replace all `extends` done on core class to an intermediate layer class in PHP.

For a Phalcon project, the `Layer` patcher can be configured like the following in the Kahlan config file:

```php
use filter\Filter;
use jit\Interceptor;
use kahlan\plugin\Layer;

Filter::register('api.patchers', function($chain) {
    if (!$interceptor = Interceptor::instance()) {
        return;
    }
    $patchers = $interceptor->patchers();
    $patchers->add('layer', new Layer([
        'override' => [
            'Phalcon\Mvc\Model' // this will dynamically apply a layer on top of the `Phalcon\Mvc\Model` to make it stubbable.
        ]
    ]));

    return $chain->next();
});

Filter::apply($this, 'patchers', 'api.patchers');
```

**Note:** You will probably need to remove all cached files in `/tmp/kahlan` (or in `sys_get_temp_dir() . '/kahlan'` if you are not on linux) to make it works.

### Working with a autoloader not compatible with PSR-0.

In this case your must implement a `PSR-0` **Composer** compatible autoloader. To have a right direction you could see at [sources](https://github.com/composer/composer/blob/master/src/Composer/Autoload/ClassLoader.php), and take care of `findFile`, `loadClass` and `add` functions.
