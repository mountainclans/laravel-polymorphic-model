<?php

namespace MountainClans\LaravelPolymorphicModel\Tests\Models;

class ChildTestModel extends BaseTestModel
{
    protected function getInstanceType(): string
    {
        return static::TYPE_CHILD;
    }
}
