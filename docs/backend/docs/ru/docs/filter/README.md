# События

Для кастомизации бэкенда с использованием событий и фильтров, которые применяются на различных этапах обработки запросов и действий. События и фильтры позволяют управлять валидацией, авторизацией, обработкой запросов и ответов, а также выполнять действия до и после событий. Основным компонентом, через который это происходит, является FilterDispatcher.

FilterDispatcher управляет всеми событиями и фильтрами, используя динамически сгенерированные названия фильтров на основе текущего маршрута, получаемого через:

```php

request()?->route()?->getName();

```

Фильтры и событий создаются по определенной схеме. Пример классов методов, которые возвращают названия фильтров:

## Запросы

Возвращает название фильтра для обработки запроса (getRequestFilterName ()):

```php

    'filter.request.' . request()?->route()?->getName();
```

Используется для фильтрации при выполнении GET запросов (getQueryFilterName ()):

```php

'filter.query.get.' . request()?->route()?->getName();
```

Применяется для добавления дополнительных отношений в запросах (getQueryAdditionalRelationsFilterName ()):

```php

    'filter.query.with.' . request()?->route()?->getName();
```

## Ответы сервера

getValidationFilterName ():
получения названия фильтра валидации:

```php

    'filter.validation.' . request()?->route()?->getName();
```

Фильтры позволяют изменять успешные и ошибочные ответы сервера. Например, можно модифицировать успешные ответы (getSuccessResponseFilterName ()):

```php

'filter.response.success.' . request()?->route()?->getName();
```

Или обработку ошибок (getErrorResponseFilterName ()):

```php

'filter.response.error.' . request()?->route()?->getName();
```

Фильтр для авторизации (getAuthFilterName ()):

```php

'filter.authorize.' . request()?->route()?->getName();
```

Используется для фильтрации данных авторизации после валидации (getAuthValidationFilterName ())

```php

    'filter.authorize.validated' . request()?->route()?->getName();
```

Событие, которое выполняется перед основным действием (getBeforeActionEventName ()):

```php

'event.before.action.' . request()?->route()?->getName();
```

Событие, которое выполняется после основного действия (getAfterActionEventName ()):

```php

    'event.after.action.' . request()?->route()?->getName();
```

Класс CattrFormRequest позволяет влиять на процесс валидации данных через метод rules (), где можно модифицировать правила валидации с использованием фильтра:

```php

public function rules(): array
{
    return Filter::process(Filter::getValidationFilterName(), $this->_rules());
}
```

Авторизация запроса также может быть модифицирована через фильтры:

```php

public function authorize(): bool
{
    return Filter::process(Filter::getAuthFilterName(), $this->_authorize());
}
```

