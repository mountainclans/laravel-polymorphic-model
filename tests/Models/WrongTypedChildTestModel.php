<?php

namespace MountainClans\LaravelPolymorphicModel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use MountainClans\LaravelPolymorphicModel\Traits\PolymorphicModel;

class WrongTypedChildTestModel extends Model
{
    use PolymorphicModel;

    protected $table = 'test_models';
    public $timestamps = false;
}
