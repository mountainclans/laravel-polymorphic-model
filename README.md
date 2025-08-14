# Laravel Polymorphic Model

Пакет, добавляющий возможность хранить в одной таблице модели разных типов, имеющих общего предка

## Установка

Установите пакет при помощи Composer:

```bash
composer require mountainclans/laravel-polymorphic-model
```

## Использование

Добавьте в родительский класс использование трейта `PolymorphicModel` и  `public static function allowedTypes(): array`, возвращающий массив допустимых типов классов-наследников.

Также для корректного сохранения модели в той же таблице явно укажите в родителе, какую таблицу он использует.

Убедитесь, что в таблице присутствует поле `type`.

```php
use MountainClans\LaravelPolymorphicModel\Traits\PolymorphicModel;

class YourParentModel {
    use PolymorphicModel;
    
    public const TYPE_TOP_BANNER = 'top_banner';
    public const TYPE_ADVANTAGES = 'advantages';
    
    protected $table = 'your_parent_model';
    
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_TOP_BANNER => TopBannerSection::class,
            self::TYPE_ADVANTAGES => AdvantagesSection::class,
        ];
    }
}
```

В классах-наследниках переопределите функцию `getInstanceType`, возвращающую тип:

```php
use MountainClans\LaravelPolymorphicModel\Traits\PolymorphicModel;

class TopBannerSection extends YourParentModel{
    protected function getInstanceType(): string
    {
        return static::TYPE_TOP_BANNER;
    }
}
```

Теперь Вы можете создавать класс-наследник напрямую и он будет сохранён в родительской таблице.
```php
$model = new TopBannerSection();
...
$model->save();
```

Вы можете извлекать в рамках одного запроса любых наследников основной модели:
```php
$collection = YourParentModel::withSubclasses()->get();
```

Или извлекать только модели нужного класса:
```php
$collection = TopBannerSection::get();
```

Или указать нужные типы в запросе с использованием where и других конструкций:
```php
$collection = YourParentModel::whereIn('type', [
    self::TYPE_TOP_BANNER, 
    self::TYPE_ADVANTAGES
])->get()
```

**Количество уровней наследования моделей не ограничено.**

### Атрибут RequiresOverride
Внутри себя трейт PolymorphicModel использует атрибут `#[RequiresOverride]`.

Вы можете использовать его для того, чтобы явно пометить, какие методы ваших моделей должны быть переопределены в классах-наследниках.

Обязательно объявите в классе использование трейта `CheckOverrides`.

```php
use MountainClans\LaravelPolymorphicModel\Traits\CheckOverrides;
use MountainClans\LaravelPolymorphicModel\Attributes\RequiresOverride;

class YourModel extends Model {
    use CheckOverrides;
    
    #[RequiresOverride]
    public function functionForOverride() {
        
    }
}
```

Если переопределение не сделано, в момент выполнения метода `boot` модели-наследника будет выброшено исключение `RequiredOverrideNotExistsException`.

## Тестирование

```bash
./vendor/bin/pest --stop-on-error
```

## Авторы

- [Vladimir Bajenov](https://github.com/mountainclans)
- [All Contributors](../../contributors)

## Лицензия

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
