<?php

namespace MountainClans\LaravelPolymorphicModel\Traits;

use MountainClans\LaravelPolymorphicModel\Attributes\RequiresOverride;
use MountainClans\LaravelPolymorphicModel\Exceptions\RequiredOverrideNotExistsException;
use ReflectionClass;
use ReflectionMethod;

trait CheckOverrides
{
    /**
     * @throws RequiredOverrideNotExistsException
     */
    public static function bootCheckOverrides(): void
    {
        static::checkOverrides();
    }

    /**
     * Проверяет наличие атрибутов RequiresOverride при первом обращении к классу-наследнику.
     *
     * @throws RequiredOverrideNotExistsException
     */
    protected static function checkOverrides(): void
    {
        $calledClass = static::class;
        $traitOwnerClass = (new ReflectionClass(__CLASS__))->getName();

        // Проверка выполняется только для классов-наследников
        if ($calledClass === $traitOwnerClass) {
            return;
        }

        $reflection = new ReflectionClass($calledClass);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $attributes = $method->getAttributes(RequiresOverride::class);
            if (!empty($attributes) && !static::isMethodOverridden($method)) {
                $message = $attributes[0]->newInstance()->message;
                throw new RequiredOverrideNotExistsException("{$calledClass}->{$method->getName()}: $message");
            }
        }
    }

    /**
     * Проверяет, переопределен ли метод в классе-наследнике.
     */
    private static function isMethodOverridden(ReflectionMethod $method): bool
    {
        $parentClass = $method->getDeclaringClass()->getParentClass();

        return $parentClass
            && $parentClass->hasMethod($method->getName())
            && $method->getDeclaringClass()->getName() !== static::class;
    }
}
