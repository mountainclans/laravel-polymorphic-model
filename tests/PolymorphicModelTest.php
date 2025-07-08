<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MountainClans\LaravelPolymorphicModel\Exceptions\PolymorphicModelPropertyIsNotExistsException;
use Tests\Models\BaseTestModel;
use Tests\Models\ChildTestModel;
use Tests\Models\AnotherChildTestModel;
use Tests\Models\WrongTypedChildTestModel;

beforeEach(function () {
    Schema::create('test_models', function (Blueprint $table) {
        $table->id();
        $table->string('type')->nullable();
        $table->string('name')->nullable();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_models');
});

it('throws if ALLOWED_TYPES is not defined', function () {
    expect(fn() => new WrongTypedChildTestModel())->toThrow(PolymorphicModelPropertyIsNotExistsException::class);
});

it('creates correct instance from builder for child', function () {
    $child = new BaseTestModel([
        'type' => BaseTestModel::TYPE_CHILD,
        'id' => 1,
        'name' => 'test'
    ]);
    expect($child)->toBeInstanceOf(ChildTestModel::class);
    expect($child->type)->toBe(BaseTestModel::TYPE_CHILD);
    expect($child->id)->toBe(1);
    expect($child->name)->toBe('test');
});

it('creates correct instance from builder for another child', function () {
    $model = new BaseTestModel();
    $another = $model->newFromBuilder(['type' => BaseTestModel::TYPE_ANOTHER_CHILD, 'id' => 2, 'name' => 'another']);
    expect($another)->toBeInstanceOf(AnotherChildTestModel::class);
    expect($another->type)->toBe(BaseTestModel::TYPE_ANOTHER_CHILD);
    expect($another->id)->toBe(2);
    expect($another->name)->toBe('another');
});

it('scopeWithSubclasses returns all allowed types', function () {
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_DEFAULT, 'name' => 'base']);
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_CHILD, 'name' => 'child']);
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_ANOTHER_CHILD, 'name' => 'another']);

    $models = BaseTestModel::query()->withSubclasses()->get();
    expect($models)->toHaveCount(3);
});

it('scopeWithoutSubclasses returns only base', function () {
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_DEFAULT, 'name' => 'base']);
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_CHILD, 'name' => 'child']);
    BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_ANOTHER_CHILD, 'name' => 'another']);

    $models = BaseTestModel::query()->withoutSubclasses()->get();
    expect($models)->toHaveCount(1);
    expect($models->first()->type)->toBe(BaseTestModel::TYPE_DEFAULT);
});

it('refresh returns correct instance for child', function () {
    $child = BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_CHILD, 'name' => 'child']);
    $another = BaseTestModel::query()->create(['type' => BaseTestModel::TYPE_ANOTHER_CHILD, 'name' => 'another']);

    $childModel = BaseTestModel::find($child->id);
    expect($childModel)->toBeInstanceOf(ChildTestModel::class);
    expect($childModel->id)->toBe($child->id);

    $anotherModel = BaseTestModel::find($another->id);
    expect($anotherModel)->toBeInstanceOf(AnotherChildTestModel::class);
    expect($anotherModel->id)->toBe($another->id);
});

it('bootPolymorphicModel sets type on saving', function () {
    $model = new BaseTestModel(['name' => 'test']);
    $model->save();
    expect($model->type)->toBe(BaseTestModel::TYPE_DEFAULT);
});
