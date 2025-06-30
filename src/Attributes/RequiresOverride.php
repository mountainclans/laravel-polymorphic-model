<?php

namespace MountainClans\LaravelPolymorphicModel\Attributes;

use Attribute;

#[Attribute]
class RequiresOverride
{
    public string $message;

    public function __construct()
    {
        $this->message = 'You must override this method.';
    }
}
