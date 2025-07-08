<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MountainClans\LaravelPolymorphicModel\Exceptions\PolymorphicModelPropertyIsNotExistsException;
use MountainClans\LaravelPolymorphicModel\Tests\Models\AnotherChildTestModel;
use MountainClans\LaravelPolymorphicModel\Tests\Models\BaseTestModel;
use MountainClans\LaravelPolymorphicModel\Tests\Models\ChildTestModel;
use MountainClans\LaravelPolymorphicModel\Tests\Models\WrongTypedBaseTestModel;

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
    expect(function() {
        $model = new WrongTypedBaseTestModel();
        $model->name = 'wrong typed model';
        $model->save();

        // todo проверить, выброс ошибок действительно должен проявляться впервые при refresh?
        $model = $model->refresh();
    })
        ->toThrow(PolymorphicModelPropertyIsNotExistsException::class);
});

it('creates correct instance from builder for child', function () {
    $model = BaseTestModel::create([
        'type' => BaseTestModel::TYPE_CHILD,
        'name' => 'test'
    ]);
    $modelId = $model->id;
    $child = $model->refresh();

    expect($child)->toBeInstanceOf(ChildTestModel::class);
    expect($child->type)->toBe(BaseTestModel::TYPE_CHILD);
    expect($child->id)->toBe($modelId);
    expect($child->name)->toBe('test');
});

it('creates correct instance from builder for another child', function () {
    $model = BaseTestModel::create([
        'type' => BaseTestModel::TYPE_ANOTHER_CHILD,
        'name' => 'another'
    ]);
    $modelId = $model->id;
    $another = $model->refresh();

    expect($another)->toBeInstanceOf(AnotherChildTestModel::class);
    expect($another->type)->toBe(BaseTestModel::TYPE_ANOTHER_CHILD);
    expect($another->id)->toBe($modelId);
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
