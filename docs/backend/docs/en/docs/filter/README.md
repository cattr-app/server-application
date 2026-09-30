# Events

To customize the backend using events and filters applied at various stages of request and action processing. Events and filters allow control over validation, authorization, request and response handling, as well as performing actions before and after events. The main component that facilitates this process is the FilterDispatcher.

The FilterDispatcher manages all events and filters by dynamically generating filter names based on the current route, obtained via:

```php

request()?->route()?->getName();

```

Filters and events are created following a specific scheme. Below are examples of class methods that return filter names:

## Requests

Returns the filter name for request processing (getRequestFilterName ()):

```php

    'filter.request.' . request()?->route()?->getName();
```

Used for filtering during GET requests (getQueryFilterName ()):

```php

'filter.query.get.' . request()?->route()?->getName();
```

Applied to add additional relations to queries (getQueryAdditionalRelationsFilterName ()):

```php

    'filter.query.with.' . request()?->route()?->getName();
```

## Server Responses

Returns the filter name for validation (getValidationFilterName ()):

```php

    'filter.validation.' . request()?->route()?->getName();
```

Filters allow modifying successful and error responses. For example, successful responses can be adjusted (getSuccessResponseFilterName ()):

```php

'filter.response.success.' . request()?->route()?->getName();
```

Or error responses (getErrorResponseFilterName ()):

```php

'filter.response.error.' . request()?->route()?->getName();
```

Authorization filter (getAuthFilterName ()):

```php

'filter.authorize.' . request()?->route()?->getName();
```

Used to filter authorization data after validation (getAuthValidationFilterName ()):

```php

    'filter.authorize.validated' . request()?->route()?->getName();
```

Event executed before the main action (getBeforeActionEventName ()):

```php

'event.before.action.' . request()?->route()?->getName();
```

Event executed after the main action (getAfterActionEventName ()):

```php

    'event.after.action.' . request()?->route()?->getName();
```

The CattrFormRequest class allows influencing the data validation process through the rules () method, where validation rules can be modified using a filter:

```php

public function rules(): array
{
    return Filter::process(Filter::getValidationFilterName(), $this->_rules());
}
```

Request authorization can also be modified via filters:

```php

public function authorize(): bool
{
    return Filter::process(Filter::getAuthFilterName(), $this->_authorize());
}
```

