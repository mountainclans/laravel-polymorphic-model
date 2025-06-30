<?php

namespace MountainClans\LaravelPolymorphicModel\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use MountainClans\LaravelPolymorphicModel\Attributes\RequiresOverride;
use MountainClans\LaravelPolymorphicModel\Exceptions\PolymorphicModelPropertyIsNotExistsException;

trait PolymorphicModel
{
    use CheckOverrides;

    public const TYPE_DEFAULT = 'default';

    #[RequiresOverride]
    protected function getInstanceType(): string
    {
        return static::TYPE_DEFAULT;
    }

    protected static function bootPolymorphicModel(): void
    {
        static::addGlobalScope('withOrWithoutSubclasses', function ($query) {
            $model = new static();

            if (!$model->hasTypeCondition($query)) {
                return $query->withSubclasses();
            }

            return $query;
        });

        static::saving(function ($model) {
            if (empty($model->type)) {
                $model->type = $model->getInstanceType();
            }
        });
    }

    /**
     * @throws PolymorphicModelPropertyIsNotExistsException
     */
    public function newFromBuilder($attributes = [], $connection = null): static
    {
        $this->checkPolymorphicModelRequirements($attributes);

        $allowedTypes = static::ALLOWED_TYPES;

        $instance = isset($allowedTypes[$attributes->type ?? null])
            ? new $allowedTypes[$attributes->type]
            : new static();

        $model = $instance->newInstance([], true);

        $model->setRawAttributes((array)$attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    /**
     * @throws PolymorphicModelPropertyIsNotExistsException
     */
    protected function checkPolymorphicModelRequirements($attributes): void
    {
        // Проверяем наличие массива ALLOWED_TYPES и поля type
        if (!defined(static::class . '::ALLOWED_TYPES')) {
            throw new PolymorphicModelPropertyIsNotExistsException(
                'Public const ALLOWED_TYPES must be defined in the model.'
            );
        }

        $attributes = (array)$attributes;

        if (!isset($attributes['type'])) {
            throw new PolymorphicModelPropertyIsNotExistsException(
                'The "type" field must be present in the attributes.'
            );
        }
    }

    public function refresh()
    {
        parent::refresh();

        $allowedTypes = static::ALLOWED_TYPES;

        $targetClass = Arr::get($allowedTypes, $this->type, static::class);

        if ($targetClass === static::class) {
            return $this;
        }

        /** @var Model $targetClass */
        return $targetClass::query()->find($this->getKey());
    }

    protected function hasTypeCondition(Builder $query): bool
    {
        return collect($query->getQuery()->wheres)->contains(function ($where) {
            return isset($where['column']) && $where['column'] === 'type';
        });
    }

    public function scopeWithSubclasses(Builder $query): Builder
    {
        $types = [];

        foreach (static::ALLOWED_TYPES as $allowedType => $allowedClass) {
            if (is_subclass_of($allowedClass, $this::class)) {
                $types[] = $allowedType;
            }
        }

        if (!in_array($this::class, $types)) {
            $types[] = $this->getInstanceType();
        }

        return $query->whereIn('type', $types);
    }

    public function scopeWithoutSubclasses(Builder $query): Builder
    {
        return $query->where('type', $this->getInstanceType());
    }
}
