# Derafu: Config - Yet Another Config Lib

![GitHub last commit](https://img.shields.io/github/last-commit/derafu/config/main)
![CI Workflow](https://github.com/derafu/config/actions/workflows/ci.yml/badge.svg?branch=main&event=push)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/derafu/config)
![GitHub Issues](https://img.shields.io/github/issues-raw/derafu/config)
![Total Downloads](https://poser.pugx.org/derafu/config/downloads)
![Monthly Downloads](https://poser.pugx.org/derafu/config/d/monthly)

## Installation

Install via Composer:

```bash
composer require derafu/config
```

## Basic Usage

```php
use Derafu\Config\Configuration; // Or `Options`.

$configuration = new Configuration([
    'app' => [
        'name' => 'Derafu Config',
    ],
]);

echo $configuration['app']['name'];           // "Derafu Config"
echo $configuration['app.name'];              // "Derafu Config"
echo $configuration->get('app.name');         // "Derafu Config"
echo $configuration->get('app')->get('name'); // "Derafu Config"
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
