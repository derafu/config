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
 * Interface for instances that want to implement a simple options system.
 */
interface OptionsAwareInterface
{
    /**
     * Sets the options to use on the instance.
     *
     * This method assigns new options to the instance.
     *
     * @param array<array-key, mixed>|OptionsInterface $options
     * @return static
     */
    public function setOptions(array|OptionsInterface $options): static;

    /**
     * Returns the options assigned to the instance.
     *
     * @return OptionsInterface
     */
    public function getOptions(): OptionsInterface;

    /**
     * Normalizes and validates the instance options.
     *
     * This method resolves options by merging them with any previously
     * assigned options and assigning the result to the instance.
     *
     * @param array<array-key, mixed> $options Options that may be unnormalized.
     * @return OptionsInterface Normalized and validated options.
     */
    public function resolveOptions(array $options): OptionsInterface;

    /**
     * Returns the schema used to validate options.
     *
     * Each key is an option and its value is that option's configuration.
     * The special key `__allowUndefinedKeys` is a `bool` indicating whether
     * options not defined in the schema are allowed.
     *
     * @return array<string,array<string,mixed>|bool>
     */
    public function getOptionsSchema(): array;
}
