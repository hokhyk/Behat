<?php

/*
 * This file is part of the Behat.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Behat\Context;

use Behat\Behat\Context\Argument\ArgumentResolver;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\Testwork\Call\FunctionArgumentResolver;
use ReflectionClass;

/**
 * Instantiates contexts using registered argument resolvers and context initializers.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
final class ContextFactory
{
    /**
     * @var FunctionArgumentResolver
     */
    private $functionArgumentResolver;
    /**
     * @var ArgumentResolver[]
     */
    private $argumentResolvers = array();
    /**
     * @var ContextInitializer[]
     */
    private $contextInitializers = array();

    /**
     * Initialises factory.
     *
     * @param FunctionArgumentResolver $functionArgumentResolver
     */
    public function __construct(FunctionArgumentResolver $functionArgumentResolver)
    {
        $this->functionArgumentResolver = $functionArgumentResolver;
    }

    /**
     * Registers context argument resolver.
     *
     * @param ArgumentResolver $resolver
     */
    public function registerArgumentResolver(ArgumentResolver $resolver)
    {
        $this->argumentResolvers[] = $resolver;
    }

    /**
     * Registers context initializer.
     *
     * @param ContextInitializer $initializer
     */
    public function registerContextInitializer(ContextInitializer $initializer)
    {
        $this->contextInitializers[] = $initializer;
    }

    /**
     * Creates and initializes context class.
     *
     * @param string $class
     * @param array  $arguments
     *
     * @return Context
     */
    public function createContext($class, array $arguments = array())
    {
        $reflection = new ReflectionClass($class);
        $arguments = $this->resolveArguments($reflection, $arguments);
        $context = $this->createInstance($reflection, $arguments);
        $this->initializeInstance($context);

        return $context;
    }

    /**
     * Resolves arguments for a specific class using registered argument resolvers.
     *
     * @param ReflectionClass $reflection
     * @param array           $arguments
     *
     * @return mixed[]
     */
    private function resolveArguments(ReflectionClass $reflection, array $arguments)
    {
        foreach ($this->argumentResolvers as $resolver) {
            $arguments = $resolver->resolveArguments($reflection, $arguments);
        }

        if (!$reflection->hasMethod('__construct') || !count($arguments)) {
            return $arguments;
        }

        return $this->functionArgumentResolver->resolveArguments(
            $reflection->getConstructor(),
            $arguments
        );
    }

    /**
     * Creates context instance.
     *
     * @param ReflectionClass $reflection
     * @param array           $arguments
     *
     * @return Context
     */
    private function createInstance(ReflectionClass $reflection, array $arguments)
    {
        if (count($arguments)) {
            return $reflection->newInstanceArgs($arguments);
        }

        return $reflection->newInstance();
    }

    /**
     * Initializes context class and returns new context instance.
     *
     * @param Context $context
     *
     * @return Context
     */
    private function initializeInstance(Context $context)
    {
        foreach ($this->contextInitializers as $initializer) {
            $initializer->initializeContext($context);
        }
    }
}
