# WPCompat

WPCompat is a PHPStan extension which helps verify that your PHP code is compatible with a given version of WordPress. You can use it to help ensure that your plugin or theme remains compatible with its "Requires at least" version.

It works by checking that any WordPress functions, class methods, parameters, array keys, actions, or filters that are in use were introduced prior to the minimum version of WordPress that your code supports. For example, if your plugin or theme supports WordPress 6.0 or higher but the `get_template_hierarchy()` function is used unconditionally, the extension will trigger an error because that function was only introduced in WordPress 6.1.

If your code is correctly guarded with a valid `function_exists()` or `method_exists()` check then an error won't be triggered.

## Status

Version information was last updated for WordPress 7.1.

## Requirements

* PHPStan 1.x or 2.x
* PHP 7.2 or higher (tested up to PHP 8.5)

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

You can ignore an error from this extension by using its error identifier. For full information, see [the PHPStan guide to ignoring errors](https://phpstan.org/user-guide/ignoring-errors).

### Functions and methods

If your code is correctly guarded with a valid `function_exists()` or `method_exists()` check then an error won't be triggered.

```php
// @phpstan-ignore WPCompat.functionNotAvailable
wp_foo();

// @phpstan-ignore WPCompat.methodNotAvailable
WP::foo();
```

### Parameters

A parameter which was added to a function or method in a later version of WordPress can be ignored using its error identifier, which contains a sanitized version of the symbol name and the parameter name.

```php
// @phpstan-ignore WPCompat.parameterNotAvailable.loadtextdomain.locale
load_textdomain( 'domain', '/path/to/file', 'en_US' );
```

The same applies to the keys of a parameter which accepts an array of arguments. Its error identifier also contains the key, or the dot delimited path to it when the key is nested.

```php
// @phpstan-ignore WPCompat.parameterKeyNotAvailable.registerposttype.args.showinrest
register_post_type( 'my_post_type', array(
	'show_in_rest' => true,
) );
```

Only keys which PHPStan can determine statically are checked, so an array which is assembled conditionally doesn't trigger an error in the first place.

### Actions and filters

There is no concept of checking the existence of an action or filter in WordPress in order to guard its usage. You can still ignore an error for an action or filter using its error identifier, which contains a sanitized version of the hook name.

```php
// @phpstan-ignore WPCompat.filterNotAvailable.filtername
add_filter( 'filter_name', 'callback' );

// @phpstan-ignore WPCompat.actionNotAvailable.myactionname
add_action( 'my_action_name', 'callback' );
```

## Technical details

This extension does not scan your project in order to detect the `@since` versions of WordPress functions, methods, parameters, array keys, and hooks. This information is included directly in the extension. This approach ensures that your code is always tested against the most up to date and most accurate `@since` documentation, regardless of the version of WordPress that your tests are using.

### Functions and methods

The [symbols.json](symbols.json) file contains a dictionary of all functions, methods, and parameters in WordPress along with the version of WordPress in which they were introduced.

Parameters which accept an array of arguments are documented in WordPress with [the hash notation](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/#1-1-parameters-that-are-arrays), which means the keys of such a parameter are known too. Any key which was introduced later than the parameter that holds it is recorded alongside it.

The file can be regenerated by running:

```shell
composer generate /path/to/wordpress
```

The JSON schema for the file can be found in [schemas/symbols.json](schemas/symbols.json).

### Actions and filters

Information about actions and filters is provided by [the `wp-hooks/wordpress-core` package](https://github.com/wp-hooks/wordpress-core-hooks).

## Sponsors

<p align="center">The time that I spend maintaining this extension and others is in part sponsored by:</p>

<p align="center"><a href="https://automattic.com"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/automattic.svg" alt="Automattic" width="50%"></a></p>

<p align="center">
    <a href="https://servmask.com"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/servmask.svg" alt="ServMask" width="25%"></a>
    &nbsp; &nbsp; &nbsp;
    <a href="https://wp-staging.com"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/wp-staging.png" alt="WP Staging" width="25%"></a>
</p>

<p align="center">Plus all my kind sponsors on GitHub:</p>

<p align="center"><a href="https://github.com/sponsors/johnbillion"><img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/sponsors.svg" alt="Sponsors"></a></p>

<p align="center"><a href="https://github.com/sponsors/johnbillion">Click here to find out about supporting my open source tools and plugins</a>.</p>

## License

MIT
