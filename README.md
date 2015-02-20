# isbn

[![Latest Version](https://img.shields.io/github/release/thephpleague/isbn.svg?style=flat-square)](https://github.com/thephpleague/isbn/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/thephpleague/isbn/master.svg?style=flat-square)](https://travis-ci.org/thephpleague/isbn)
[![Coverage Status](https://img.shields.io/scrutinizer/coverage/g/thephpleague/isbn.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/isbn/code-structure)
[![Quality Score](https://img.shields.io/scrutinizer/g/thephpleague/isbn.svg?style=flat-square)](https://scrutinizer-ci.com/g/thephpleague/isbn)
[![Total Downloads](https://img.shields.io/packagist/dt/league/isbn.svg?style=flat-square)](https://packagist.org/packages/league/isbn)

Handling of ISBN validation and formatting. Can use ranges downloaded from https://www.isbn-international.org/range_file_generation.

## Install

Via Composer

``` bash
$ composer require agnarodegard/isbn
```

## Usage

``` php
require_once "vendor/autoload.php";
$isbn = new \Agnarodegard\Isbn\Isbn('978-82-15-01538-5');

// This is the user input.
$isbn->isbn

// User input with all illegal characters removed
$isbn->unformatted

// Returns 'ISBN10', 'ISBN13' or false.
$isbn->type()

// Returns true or false
$isbn->valid

// Returns hyphenated ISBN.
$isbn->hyphenate()

// Returns ISBN with spaces instead of hyphens.
$isbn->hyphenate(' ')

// Calculates the checkDigit of an ISBN.
$isbn->checkDigit()

// Conversion between ISBN10 and ISBN13 not implemented yet.
```

## Testing

``` bash
$ php vendor/phpunit/phpunit/phpunit
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email agnar@norweb.no instead of using the issue tracker.

## Credits

- [Agnar Ødegård](https://github.com/agnarodegard)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
