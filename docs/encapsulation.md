# Driver Encapsulation

This section is going to be pretty short, but basically I don't think it's a good idea architecturally to use the base object in a project or framework without wrapping it in a class that interfaces with your code. For a command line script it's fine, but once you need to make document models and access it everywhere it's going to get messy. Instead I'm going to give some ideas on how you can manage this better.

## Driver Class Wrapper
This allows you to interface the same database connection and configuration throughout your entire application. It also gives you the flexibility to wrap the raw driver methods to do further manipulation (like sorting). I choose to use a `Singleton` pattern for ease of testing  however, a plain old `class` with `static` methods is perfectly fine too! You do what works best with your needs.

### Example
```
namespace YourProject\Drivers;
use YourProject\Models\ConfigSet as ConfigSet;
use \DelightfulDB\Driver as Driver;

class DelightfulDB {
  private $_db = null;

  static public function getInstance() : DelightfulDB
  {
    static $instance = false;
    return ($instance) ? $instance : $instance = new DelightfulDB()
  }

  public function __construct()
  {
    $root = ConfigSet::get('delightfuldb')->get('root');
    $stores = ConfigSet::get('delightfuldb')->get('stores');
    $timeout = ConfigSet::get('delightfuldb')->get('timeout');

    $this->_db = new Driver($root, $stores, $timeout);
  }

  public function query() : DelightfulDB
  {
    return $this->_db;
  }

  // Additional Wrapper Functions Here
}
```

You can expand or change this as you desire. This is just a basic example.


## Document Models
By the nature of the engine, it just returns raw structs. However, using the encapsulating method above you can abstract the query functions further to have them interact with a custom model if you so desire.

### Examples

```
abstract class DelightfulModel {
  private $_id = '';
  private $_document = [];

  public function load(String $id)
  {
    // Load Document Into Model
  }

  public function write()
  {
    // Call Database To Create or Update
  }

  // Model Functions Here
}
```

Again, this is just an example, but you do what is best for your framework.

## Why Didn't I Build These In?
As with my opinion with PSR-4 autoloading, it's *opinionated* and I don't like forcing people to do things if they don't want to. The library is basic by design so that ***you*** the ***engineer*** can make those decisions architecturally. Too many times libraries and frameworks hold your hand (*cough*, laravel, *cough*) and you get no say, choice, or even worse, you have no idea how any of it works. I didn't want that and I believe you don't want that either.
