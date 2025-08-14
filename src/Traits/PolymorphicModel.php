<?php

namespace MountainClans\LaravelPolymorphicModel\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use MountainClans\LaravelPolymorphicModel\Attributes\RequiresOverride;
use MountainClans\LaravelPolymorphicModel\Exceptions\PolymorphicModelPropertyIsNotExistsException;
use ReflectionMethod;

trait PolymorphicModel
{
    use CheckOverrides;

    protected static array $allowedTypesCheckCache = [];
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

        $allowedTypes = static::allowedTypes();

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
        $class = static::class;

        // Кешируем результат, чтобы не гонять рефлексию постоянно
        if (isset(self::$allowedTypesCheckCache[$class])) {
            return;
        }

        if (!method_exists($class, 'allowedTypes')) {
            throw new PolymorphicModelPropertyIsNotExistsException(
                'Public static method allowedTypes() must be defined in the model.'
            );
        }

        $ref = new ReflectionMethod($class, 'allowedTypes');

        if (!$ref->isPublic() || !$ref->isStatic()) {
            throw new PolymorphicModelPropertyIsNotExistsException(
                'Method allowedTypes() must be public and static in the model.'
            );
        }

        $returnType = $ref->getReturnType();
        if (!$returnType || $returnType->getName() !== 'array') {
            throw new PolymorphicModelPropertyIsNotExistsException(
                'Method allowedTypes() must have return type array in the model.'
            );
        }

        // Проставляем в кеш
        self::$allowedTypesCheckCache[$class] = true;

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

        $allowedTypes = static::allowedTypes();

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

        foreach (static::allowedTypes() as $allowedType => $allowedClass) {
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
