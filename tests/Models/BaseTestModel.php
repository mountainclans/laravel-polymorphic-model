<?php

namespace MountainClans\LaravelPolymorphicModel\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use MountainClans\LaravelPolymorphicModel\Traits\PolymorphicModel;

class BaseTestModel extends Model
{
    use PolymorphicModel;

    protected $table = 'test_models';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
    ];

    public const TYPE_CHILD = 'child';
    public const TYPE_ANOTHER_CHILD = 'another_child';

    public const ALLOWED_TYPES = [
        self::TYPE_DEFAULT => self::class,
        self::TYPE_CHILD => ChildTestModel::class,
        self::TYPE_ANOTHER_CHILD => AnotherChildTestModel::class,
    ];

    protected function getInstanceType(): string
    {
        return static::TYPE_DEFAULT;
    }
}
