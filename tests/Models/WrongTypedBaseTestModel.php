<?php

namespace MountainClans\LaravelPolymorphicModel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use MountainClans\LaravelPolymorphicModel\Traits\PolymorphicModel;

class WrongTypedBaseTestModel extends Model
{
    use PolymorphicModel;

    public const TYPE_WRONG = 'wrong';

    protected $table = 'test_models';
    public $timestamps = false;
}
