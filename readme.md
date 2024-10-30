# WPCompat

WPCompat is a PHPStan extension which helps verify that your PHP code is compatible with a given version of WordPress. You can use it to help ensure that your plugin or theme remains compatible with its "Requires at least" version.

It works by checking that the declared `@since` version of any WordPress functions or class methods that are in use is lower than or equal to the minimum version of WordPress that your code supports. For example, if your plugin or theme supports WordPress 6.0 or higher but the `get_template_hierarchy()` function is used unconditionally, the extension will trigger an error because that function was only introduced in WordPress 6.1.

If your code is correctly guarded with a valid `function_exists()` or `method_exists()` check then an error won't be triggered.

## Status

WPCompat is a brand new extension and not yet exhaustive in its checks. Version 1.0 will be released once it's stable.

Version information for functions and methods was last updated for WordPress 6.7 (RC1).

## Requirements

* PHPStan 1.12 or higher
* PHP 7.4 or higher (tested up to PHP 8.3)

## Installation

```shell
composer require --dev johnbillion/wp-compat
```

If you also install [phpstan/extension-installer](https://github.com/phpstan/extension-installer) then you're all set!

<details>
  <summary>Manual installation</summary>

If you don't want to use `phpstan/extension-installer`, include extension.neon in your project's PHPStan config:

```neon
includes:
    - vendor/johnbillion/wp-compat/extension.neon
```
</details>

## Configuration

### Themes

If your style.css file contains a "Requires at least" header then wp-compat will read this header and use its value as the minimum supported WordPress version. There is no need for any additional config.

### Plugins

If the name of your main plugin file matches its parent directory -- for example `my-plugin/my-plugin.php` -- then wp-compat will read the "Requires at least" header from this file and use its value as the minimum supported WordPress version. There is no need for any additional config.

If your main plugin file is named otherwise or located elsewhere, you can specify its name in your PHPStan config file:

```neon
parameters:
    WPCompat:
        pluginFile: my-plugin.php
```

### Manual config

Alternatively you can specify the minimum supported WordPress version number of your plugin or theme directly in your PHPStan config file. Note that this must be a string so it must be wrapped in quote marks.

```neon
parameters:
    WPCompat:
        requiresAtLeast: '6.0'
```

Any version number in `major.minor` or `major.minor.patch` format is accepted.

## Ignoring errors

You can ignore an error from this extension by using its error identifiers. For full information, see [the PHPStan guide to ignoring errors](https://phpstan.org/user-guide/ignoring-errors).

```php
// @phpstan-ignore WPCompat.functionNotAvailable
wp_foo();

// @phpstan-ignore WPCompat.methodNotAvailable
WP::foo();
```

## Technical details

This extension does not scan your project in order to detect the `@since` versions of WordPress functions and methods. This information is included in the [symbols.json](symbols.json) file that's included in the extension.

The [symbols.json](symbols.json) file contains a dictionary of all functions and methods in WordPress along with the version of WordPress in which they were introduced.

The file can be regenerated by running:

```shell
composer generate
```

The JSON schema for the file can be found in [schemas/symbols.json](schemas/symbols.json).

## License

MIT
