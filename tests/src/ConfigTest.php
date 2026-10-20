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
use Derafu\Config\Contract\ConfigurationInterface;
use Derafu\Config\Contract\OptionsAwareInterface;
use Derafu\Config\Contract\OptionsInterface;
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
    /** @var array<array-key, mixed> */
    private array $configuration = [
        'app' => [
            'name' => 'Derafu Config',
        ],
    ];

    /** @var array<array-key, mixed> */
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

        $app = $configuration['app'];
        $this->assertInstanceOf(ConfigurationInterface::class, $app);
        $this->assertSame('Derafu Config', $app['name']);
        $this->assertSame('Derafu Config', $configuration['app.name']);
        $this->assertSame('Derafu Config', $configuration->get('app.name'));
        $nestedApp = $configuration->get('app');
        $this->assertInstanceOf(ConfigurationInterface::class, $nestedApp);
        $this->assertSame('Derafu Config', $nestedApp->get('name'));
    }

    public function testOptions(): void
    {
        $options = new Options($this->options);

        $this->assertSame('template.pdf', $options->get('strategy'));
        $pdf = $options->get('config.pdf');
        $this->assertInstanceOf(OptionsInterface::class, $pdf);
        $this->assertTrue($pdf->get('footer'));
    }

    public function testConfigurable(): void
    {
        $configurableClass = new ConfigurableClass();
        $configurableClass->setConfiguration($this->configuration);

        $this->assertSame('Derafu Config', $configurableClass->getConfiguration()->get('app.name'));
        $app = $configurableClass->getConfiguration()->get('app');
        $this->assertInstanceOf(ConfigurationInterface::class, $app);
        $this->assertSame('Derafu Config', $app->get('name'));
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
        $pdf = $optionsAwareClass->getOptions()->get('config.pdf');
        $this->assertInstanceOf(OptionsInterface::class, $pdf);
        $this->assertTrue($pdf->get('footer'));
    }

    public function testOptionsAwareWithDefaultOptions(): void
    {
        $optionsAwareClass = new OptionsAwareClass();

        $this->assertSame('template.html', $optionsAwareClass->getOptions()->get('strategy'));
        $pdf = $optionsAwareClass->getOptions()->get('config.pdf');
        $this->assertInstanceOf(OptionsInterface::class, $pdf);
        $this->assertFalse($pdf->get('footer'));
    }

    public function testConfigurationIsInitializedLazilyWithSchemaDefaults(): void
    {
        $configurable = new ConfigurableClass();
        $configuration = $configurable->getConfiguration();
        $this->assertSame(['app' => ['name' => 'Derafu App']], $configuration->all());
        $this->assertSame($configuration, $configurable->getConfiguration());
    }

    public function testResolvingBeforeInitializationAppliesSchemaDefaults(): void
    {
        $configurable = new ConfigurableClass();
        $configuration = $configurable->resolveConfiguration();
        $this->assertSame(['app' => ['name' => 'Derafu App']], $configuration->all());
        $this->assertSame($configuration, $configurable->getConfiguration());

        $optionsAware = new OptionsAwareClass();
        $options = $optionsAware->resolveOptions(['strategy' => 'template.pdf']);
        $this->assertSame('template.pdf', $options->get('strategy'));
        $this->assertFalse($options->get('config.pdf.footer'));
        $this->assertSame($options, $optionsAware->getOptions());
    }

    public function testResolvingConfigurationMergesTopLevelValuesWithoutChangingThePreviousContainer(): void
    {
        $original = ['nested' => ['keep' => 1, 'replace' => 2], 'keep' => true];
        $configurable = new ConfigurableClassWithoutSchema();
        $configurable->setConfiguration($original);
        $previous = $configurable->getConfiguration();

        $resolved = $configurable->resolveConfiguration(['nested' => ['new' => 3], 'added' => false]);
        $this->assertSame(['nested' => ['new' => 3], 'keep' => true, 'added' => false], $resolved->all());
        $this->assertNotSame($previous, $resolved);
        $this->assertSame($original, $previous->all());
        $this->assertSame($resolved, $configurable->getConfiguration());
        $this->assertSame($resolved->all(), $configurable->resolveConfiguration()->all());
    }

    public function testResolvingOptionsMergesTopLevelValuesWithoutChangingThePreviousContainer(): void
    {
        $original = ['nested' => ['keep' => 1, 'replace' => 2], 'keep' => true];
        $optionsAware = new OptionsAwareClassWithoutSchema();
        $optionsAware->setOptions($original);
        $previous = $optionsAware->getOptions();

        $resolved = $optionsAware->resolveOptions(['nested' => ['new' => 3], 'added' => false]);
        $this->assertSame(['nested' => ['new' => 3], 'keep' => true, 'added' => false], $resolved->all());
        $this->assertNotSame($previous, $resolved);
        $this->assertSame($original, $previous->all());
        $this->assertSame($resolved, $optionsAware->getOptions());
        $this->assertSame($resolved->all(), $optionsAware->resolveOptions()->all());
    }

    public function testSettersKeepSuppliedContainerInstances(): void
    {
        $configuration = new Configuration(['name' => 'shared']);
        $configurable = new ConfigurableClassWithoutSchema();
        $this->assertSame($configurable, $configurable->setConfiguration($configuration));
        $this->assertSame($configuration, $configurable->getConfiguration());
        $configuration->set('name', 'updated');
        $this->assertSame('updated', $configurable->getConfiguration()->get('name'));

        $options = new Options(['enabled' => true]);
        $optionsAware = new OptionsAwareClassWithoutSchema();
        $this->assertSame($optionsAware, $optionsAware->setOptions($options));
        $this->assertSame($options, $optionsAware->getOptions());
        $options->set('enabled', false);
        $this->assertFalse($optionsAware->getOptions()->get('enabled'));
    }

    public function testConfigurationRejectsAnInvalidChangeAndKeepsTheData(): void
    {
        $configurable = new ConfigurableClass();
        $configuration = $configurable->getConfiguration();

        try {
            $configuration->set('app.name', false);
            $this->fail('An invalid change must be rejected.');
        } catch (InvalidOptionsException) {
            $this->assertSame('Derafu App', $configurable->getConfiguration()->get('app.name'));
        }
    }

    public function testOptionsRejectAnInvalidChangeAndKeepTheData(): void
    {
        $optionsAware = new OptionsAwareClass();
        $options = $optionsAware->getOptions();

        try {
            $options->set('strategy', 'template.doc');
            $this->fail('An invalid change must be rejected.');
        } catch (InvalidOptionsException) {
            $this->assertSame('template.html', $optionsAware->getOptions()->get('strategy'));
        }
    }

    public function testNestedValuesKeepTheClassOfTheContainer(): void
    {
        $options = new ConfigTestOptions(['app' => ['name' => 'Derafu']]);
        $configuration = new Configuration(['app' => ['name' => 'Derafu']]);

        $this->assertInstanceOf(ConfigTestOptions::class, $options->get('app'));
        $this->assertInstanceOf(ConfigTestOptions::class, $options['app']);
        $this->assertInstanceOf(Configuration::class, $configuration->get('app'));
    }

    public function testTypedGettersAreAvailable(): void
    {
        $options = new Options(['app' => ['name' => 'Derafu', 'port' => 8080]]);
        $configuration = new Configuration(['app' => ['name' => 'Derafu', 'port' => 8080]]);

        $this->assertSame('Derafu', $options->requireString('app.name'));
        $this->assertSame(8080, $options->requireContainer('app')->requireInt('port'));
        $this->assertInstanceOf(Options::class, $options->requireContainer('app'));
        $this->assertSame('Derafu', $configuration->requireString('app.name'));
        $this->assertInstanceOf(Configuration::class, $configuration->requireContainer('app'));
    }

    public function testInvalidResolutionPreservesThePreviousConfiguration(): void
    {
        $configurable = new ConfigurableClass();
        $previous = $configurable->getConfiguration();
        try {
            $configurable->resolveConfiguration(['app' => ['name' => false]]);
            $this->fail('An invalid resolved configuration must be rejected.');
        } catch (InvalidOptionsException $exception) {
            $this->assertStringContainsString('name', $exception->getMessage());
            $this->assertSame($previous, $configurable->getConfiguration());
            $this->assertSame(['app' => ['name' => 'Derafu App']], $previous->all());
        }
    }

    public function testInvalidResolutionPreservesThePreviousOptions(): void
    {
        $optionsAware = new OptionsAwareClass();
        $previous = $optionsAware->getOptions();
        try {
            $optionsAware->resolveOptions(['strategy' => 'template.doc']);
            $this->fail('Invalid resolved options must be rejected.');
        } catch (InvalidOptionsException $exception) {
            $this->assertStringContainsString('strategy', $exception->getMessage());
            $this->assertSame($previous, $optionsAware->getOptions());
            $this->assertSame('template.html', $previous->get('strategy'));
        }
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

    /** @var array<string, array<string, mixed>> */
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

    /** @var array<string, array<string, mixed>> */
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

    /** @var array<string, array<string, mixed>> */
    protected array $optionsSchema = [
        'strategy' => ['types' => 'string'],
    ];
}

class ConfigTestOptions extends Options
{
}
