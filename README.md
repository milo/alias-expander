[AliasExpander](https://github.com/milo/alias-expander/blob/master/AliasExpander.php)
=====================================================================================
It is a tool for run-time class alias expanding to its fully qualified name. In brief, it is a workaround for missing `::class` constant from PHP 5.5 in PHP 5.3+ and a helper for annotations processing.

```php
# An ordinary 'use' usage in namespaced code. But how to expand the alias to full class name?
use Other\Lib as OL;

# in PHP 5.5+
echo OL::class;  // 'Other\Lib'

# in PHP 5.3+
echo Alias::expand('OL');  // 'Other\Lib'


# If the static call is too long for you, wrap it in own function. It will be easy to replace
# when upgrade to PHP 5.5.
function aliasFqn($alias) {
	return \Milo\Alias::expand($alias, 1);
}


# Due to performance, it is good to set writable directory for caching.
Alias::getExpander()->setCacheDir('/path/to/tmp');


# If you want to be strict and ensure that alias expands only to defined class name,
# set exists checking. This is a debugging advantage against to ::class in PHP 5.5.
Alias::getExpander()->setExistsCheck(TRUE);
# or
Alias::getExpander()->setExistsCheck(E_USER_WARNING);


# Expanding an alias in explicitly specified file and line context is useful
# for annotations processing.
$method = new ReflectionMethod($object, 'method');
Alias::expandExplicit('NS\Alias', $method->getFileName(), $method->getStartLine());


# The Milo\Alias class is only a static wrapper for the Milo\AliasExpander object.
# You can use a non-static variation in the same way.
$expander = new Milo\AliasExpander;
$expander->expand('OL');
$expander->expandExplicit('OL', $file, $line);
$expander->setCacheDir('/path/to/tmp');
...
```

If you know the [Nette Framework](http://nette.org), there is a [prepared](https://github.com/milo/alias-expander/blob/master/Nette/AliasExpander.php) version of expander for using with Nette\Cache.
```php
$storage = new Nette\Caching\Storages\FileStorage('/path/to/tmp');
$expander = new Milo\Nette\AliasExpander($storage);
```



There are some limitations:
- One line code like `namespace First; AliasExpander::expand('Foo'); namespace Second;` may leads to wrong expanding. It is not so easy to implement it because PHP tokenizer and debug_backtrace() provides only line number, but not the column. This can be a problem in minified code.
- Keywords `self`, `static` and `parent` are not expanded as in PHP 5.5, but this can be easily solved by `__CLASS__`, `get_called_class()` and `get_parent_class()` instead of AliasExpander using.



Licence
=======
You may use all files under the terms of the New BSD Licence, or the GNU Public Licence (GPL) version 2 or 3, or the MIT Licence.



Tests
=====
The AliasExpander tests are written for [Nette Tester](https://github.com/nette/tester). Two steps are required to run them:
```sh
# Download the Tester tool
composer.phar update --dev

# Run the tests
vendor/bin/tester tests
```
