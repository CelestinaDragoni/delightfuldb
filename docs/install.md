# Installation

## Composer Installation

### Preface
This document isn't going over how to install composer in your project, I recommend you head over to https://getcomposer.org/ to learn how to do so.

### Add Package
From your command line console in the root of your project do the following:

```
composer require celestinadragoni/delightfuldb
```

That's it, it should just work and you should be good to go.


## Manual Installation

### Download or Clone
Download or clone this repo into your project. The `master` will always be stable, however if you want a specific tag/version check the tags list.

### Loading Driver
We're going to assume you put this into a `library/` folder for your project, however change this path to match yours. To load the driver into your code simply do the following:

```
require_once('./library/driver.php');
```
The class will do all of the auto loading for you without you having to worry about it.

## Next Steps
Continue to the [Configuration, Initialization, and Error Handling](configuration.md) section to start using the database engine.
