<?php

declare(strict_types=1);

/**
 * Derafu: Config - Yet Another Config Lib.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Config\Contract;

/**
 * Interface for instances that want to implement a simple configuration system.
 */
interface ConfigurableInterface
{
    /**
     * Assigns a configuration to the instance.
     *
     * @param array<array-key, mixed>|ConfigurationInterface $configuration
     * @return static
     */
    public function setConfiguration(
        array|ConfigurationInterface $configuration
    ): static;

    /**
     * Returns the instance configuration.
     *
     * @return ConfigurationInterface
     */
    public function getConfiguration(): ConfigurationInterface;

    /**
     * Normalizes and validates the instance configuration.
     *
     * @param array<array-key, mixed> $configuration Configuration that may be unnormalized.
     * @return ConfigurationInterface Normalized and validated configuration.
     */
    public function resolveConfiguration(
        array $configuration
    ): ConfigurationInterface;

    /**
     * Returns the schema used to validate configurations.
     *
     * Each key is a configuration and its value is that configuration's
     * settings. The special key `__allowUndefinedKeys` is a `bool` indicating
     * whether configurations not defined in the schema are allowed.
     *
     * @return array<string,array<string,mixed>|bool>
     */
    public function getConfigurationSchema(): array;
}
