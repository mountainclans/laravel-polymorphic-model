<?php

namespace MountainClans\LaravelPolymorphicModel\Tests\Models;

class AnotherChildTestModel extends BaseTestModel
{
    protected function getInstanceType(): string
    {
        return static::TYPE_ANOTHER_CHILD;
    }
}
