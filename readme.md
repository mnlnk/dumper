## Dumper 2.0.0

PHP дампер для отладки кода.

##### Требования:
+ `PHP >= 8.1`
+ `ext-mbstring`

#### Установка:
```
composer require mnlnk/dumper
```

#### Примеры:
```php
// Выводит дамп данных
dump($data);
```
```php
// Выводит дамп данных и завершает выполнение скрипта
dumpEx($data);
```
