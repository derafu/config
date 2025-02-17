# Derafu: Config - Yet Another Config Lib

[![CI Workflow](https://github.com/derafu/config/actions/workflows/ci.yml/badge.svg?branch=main&event=push)](https://github.com/derafu/config/actions/workflows/ci.yml?query=branch%3Amain)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

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

echo $configuration['app']['name']; // "Derafu Config"
echo $configuration['app.name']; // "Derafu Config"
echo $configuration->get('app.name'); // "Derafu Config"
echo $configuration->get('app')->get('name'); // "Derafu Config"
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This library is licensed under the MIT License. See the `LICENSE` file for more details.
