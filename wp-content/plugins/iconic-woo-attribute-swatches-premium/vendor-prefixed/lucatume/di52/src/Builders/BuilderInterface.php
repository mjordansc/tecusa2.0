<?php
/**
 * The API provided by each builder.
 *
 * @package lucatume\DI52
 *
 * @license GPL-3.0
 * Modified by James Kemp on 28-April-2025 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Iconic_WAS_NS\lucatume\DI52\Builders;

/**
 * Interface BuilderInterface
 *
 * @package Iconic_WAS_NS\lucatume\DI52\Builders
 */
interface BuilderInterface
{
    /**
     * Builds and returns the implementation handled by the builder.
     *
     * @return mixed The implementation provided by the builder.
     */
    public function build();
}
