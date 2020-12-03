# Object mapper for laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nddcoder/laravel-object-mapper.svg?style=flat-square)](https://packagist.org/packages/nddcoder/laravel-object-mapper)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/nddcoder/laravel-object-mapper/run-tests?label=tests)](https://github.com/nddcoder/laravel-object-mapper/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/nddcoder/laravel-object-mapper.svg?style=flat-square)](https://packagist.org/packages/nddcoder/laravel-object-mapper)


This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require nddcoder/laravel-object-mapper
```

You can publish the config file with:
```bash
php artisan vendor:publish --provider="Nddcoder\ObjectMapper\ObjectMapperServiceProvider" --tag="config"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

``` php
$laravel-object-mapper = new Nddcoder\ObjectMapper();
echo $laravel-object-mapper->echoPhrase('Hello, Nddcoder!');
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Dung Nguyen Dang](https://github.com/dangdungcntt)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
