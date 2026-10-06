<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\TestsConfig;

use Derafu\Config\Configuration;
use Derafu\Config\Contract\ConfigurableInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use Derafu\Config\Options;
use Derafu\Config\Trait\ConfigurableTrait;
use Derafu\Config\Trait\OptionsAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

#[CoversClass(Configuration::class)]
#[CoversClass(Options::class)]
#[CoversTrait(ConfigurableTrait::class)]
#[CoversTrait(OptionsAwareTrait::class)]
class ConfigTest extends TestCase
{
    private array $configuration = [
        'app' => [
            'name' => 'Derafu Config',
        ],
    ];

    private array $options = [
        'strategy' => 'template.pdf',
        'config' => [
            'html' => [
                'footer' => false,
            ],
            'pdf' => [
                'footer' => true,
            ],
        ],
    ];

    public function testConfiguration(): void
    {
        $configuration = new Configuration($this->configuration);

        $this->assertSame('Derafu Config', $configuration['app']['name']);
        $this->assertSame('Derafu Config', $configuration['app.name']);
        $this->assertSame('Derafu Config', $configuration->get('app.name'));
        $this->assertSame('Derafu Config', $configuration->get('app')->get('name'));
    }

    public function testOptions(): void
    {
        $options = new Options($this->options);

        $this->assertSame('template.pdf', $options->get('strategy'));
        $this->assertTrue($options->get('config.pdf')->get('footer'));
    }

    public function testConfigurable(): void
    {
        $configurableClass = new ConfigurableClass();
        $configurableClass->setConfiguration($this->configuration);

        $this->assertSame('Derafu Config', $configurableClass->getConfiguration()->get('app.name'));
        $this->assertSame('Derafu Config', $configurableClass->getConfiguration()->get('app')->get('name'));
    }

    public function testSchemaIsEmptyByDefault(): void
    {
        $this->assertSame([], (new ConfigurableClassWithoutSchema())->getConfigurationSchema());
        $this->assertSame([], (new OptionsAwareClassWithoutSchema())->getOptionsSchema());

        // Without schema, any configuration or option is accepted.
        $configurable = new ConfigurableClassWithoutSchema();
        $this->assertSame('x', $configurable->setConfiguration(['any' => 'x'])->getConfiguration()->get('any'));
        $optionsAware = new OptionsAwareClassWithoutSchema();
        $this->assertSame('x', $optionsAware->setOptions(['any' => 'x'])->getOptions()->get('any'));
    }

    public function testSchemaIsTheOneOfTheOverride(): void
    {
        $this->assertSame(['app'], array_keys((new ConfigurableClass())->getConfigurationSchema()));
        $this->assertSame(['strategy', 'config'], array_keys((new OptionsAwareClass())->getOptionsSchema()));
    }

    public function testPropertyAloneIsNotTheSchema(): void
    {
        // The schema is what the method returns: a property that the class
        // declares, but that its method does not return, is not read.
        $this->assertSame([], (new ClassWithPropertyButNoOverride())->getOptionsSchema());
    }

    public function testOptionsAware(): void
    {
        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions($this->options);

        $this->assertSame('template.pdf', $optionsAwareClass->getOptions()->get('strategy'));
        $this->assertTrue($optionsAwareClass->getOptions()->get('config.pdf')->get('footer'));
    }

    public function testOptionsAwareWithDefaultOptions(): void
    {
        $optionsAwareClass = new OptionsAwareClass();

        $this->assertSame('template.html', $optionsAwareClass->getOptions()->get('strategy'));
        $this->assertFalse($optionsAwareClass->getOptions()->get('config.pdf')->get('footer'));
    }

    public function testOptionsAwareWithWrongOptionName(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions([
            'strategi' => 'template.pdf',
        ]);
    }

    public function testOptionsAwareWithWrongOptionValue(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $optionsAwareClass = new OptionsAwareClass();
        $optionsAwareClass->setOptions([
            'strategy' => 'template.doc',
        ]);
    }
}

class ConfigurableClass implements ConfigurableInterface
{
    use ConfigurableTrait;

    protected array $configurationSchema = [
        'app' => [
            'types' => 'array',
            'schema' => [
                'name' => [
                    'types' => 'string',
                    'default' => 'Derafu App',
                ],
            ],
        ],
    ];

    public function getConfigurationSchema(): array
    {
        return $this->configurationSchema;
    }
}

class ConfigurableClassWithoutSchema implements ConfigurableInterface
{
    use ConfigurableTrait;
}

class OptionsAwareClass implements OptionsAwareInterface
{
    use OptionsAwareTrait;

    protected array $optionsSchema = [
        'strategy' => [
            'types' => 'string',
            'choices' => ['template.html', 'template.pdf'],
            'default' => 'template.html',
        ],
        'config' => [
            'types' => 'array',
            'schema' => [
                'html' => [
                    'types' => 'array',
                    'schema' => [
                        'footer' => [
                            'types' => 'bool',
                            'default' => false,
                        ],
                    ],
                ],
                'pdf' => [
                    'types' => 'array',
                    'schema' => [
                        'footer' => [
                            'types' => 'bool',
                            'default' => false,
                        ],
                    ],
                ],
            ],
        ],
    ];

    public function getOptionsSchema(): array
    {
        return $this->optionsSchema;
    }
}

class OptionsAwareClassWithoutSchema implements OptionsAwareInterface
{
    use OptionsAwareTrait;
}

class ClassWithPropertyButNoOverride implements OptionsAwareInterface
{
    use OptionsAwareTrait;

    protected array $optionsSchema = [
        'strategy' => ['types' => 'string'],
    ];
}
