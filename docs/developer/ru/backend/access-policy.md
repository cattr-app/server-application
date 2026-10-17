# Политика доступа

Права пользователя в системе определяются:

- флагами __role_id__ пользователя;
- трейтом HasRole;
- стандартной ролью пользователя;
- ролью пользователя в проектах.

С помощью трейта проверяется HasRole методом isAdmin () является ли пользователь администратором, тогда пользователь имеет полный доступ ко всем объектам системы.

Роль даёт пользователю набор разрешительных прав.

При проверке доступа к объектам с помощью getProjectRole, относящимся к определённому проекту, права стандартной и проектной ролей пользователя оъединяются. (Т.е., если пользователь имеет стандартную роль __manager__ и роль в проекте __user__, он имеет над объектами проекта все права ролей __user__ и __manager__ .)

Пользователи имеют безусловный доступ к:

- просмотру назначенных им задач и проектов;
- созданию, просмотру и удалению своих скриншотов и интервалов в назначенных им проектах.

Пользователи могут редактировать свои скриншоты и интервалы.

## Стандартные роли

- __user__ - может создавать интервалы и скриншоты;
- __auditor__ - также имеет доступ на чтение к пользователям, проектам, задачам; может создавать задачи;
- __manager__ - может просматривать, создавать и редактировать пользователей; просматривать, создавать, редактировать и удалять проекты и задачи.

## Имплементация

При обработке запроса вначале проверяется, имеет ли пользователь доступ к эндпоинту. Это реализовано в hasRoleInAnyProject роли пользователей кешируются, и выполняется проверка на соответствие переданной роли :

```php
  final public function hasRoleInAnyProject(Role|array $role): bool
    {
        $self = $this;
        $roles = Cache::store('octane')->remember(
            "role_any_project_$self->id",
            config('cache.role_caching_ttl'),
            static fn() => $self->projectsRelation()
                ->get(['role_id'])
                ->keyBy('role_id')
                ->map(static fn($el) => $el->role_id)
                ->all(),
        );

        if (is_array($role)) {
            foreach ($role as $e) {
                if (isset($roles[$e->value])) {
                    return true;
                }
            }
        } elseif (isset($roles[$role->value])) {
            return true;
        }

        return false;
    }
```

Для части прав (users.list, users.show, users.edit, project.list, project.show, task.list, task.show, screenshots.list, screenshots.show, screenshots.edit, screenshots.remove, time-intervals.list, time-intervals.show, time-intervals.edit, time-intervals.remove) проверка на уровне эндпоинта не производится. Для них на уровне запроса производится фильтрация объектов, к которым имеет доступ пользователь с помощью ItemController.

---

Метод контроллера `getControllerRules()` возвращает ассоциативный массив, сопоставляющий действия контроллера требуемым правам доступа. Например реализация в `TimeController`:

```php
public static function getControllerRules(): array
    {
        return [
            'total' => 'time.total',
            'project' => 'time.project',
            'tasks' => 'time.tasks',
            'task' => 'time.task',
            'taskUser' => 'time.task-user',
        ];
    }
```

---

Метод user ()->can () Laravel проверяет наличие соответствующего разрешения через политику авторизации, имеет ли пользователь право выполнять определённое действие над указанным типом объектов. К примеру класс CreateTaskRequest.

```php
public function authorizeValidated(): bool
    {
        return $this->user()->can('create', [Task::class, $this->get('project_id')]);
    }
```

может ли текущий пользователь (которого мы получаем через метод user ()) создать задачу в моделе __Task__ в текущем конкретном проекте __project_id__

---

В методе контроллера ItemController `getQuery()`  создает запрос к базе данных для указанной модели, применяет глобальные скопы, дополнительные отношения, фильтры, и возвращает модифицированный объект запроса.

```php
 protected function getQuery(array $filter = []): Builder
    {
        $model = static::MODEL;
        $model = new $model;

        $query = new Builder($model::getQuery());
        $query->setModel($model);

        $modelScopes = $model->getGlobalScopes();

        foreach ($modelScopes as $key => $value) {
            $query->withGlobalScope($key, $value);
        }

        foreach (Filter::process(Filter::getQueryAdditionalRelationsFilterName(), []) as $with) {
            $query->with($with);
        }

        QueryHelper::apply($query, $model, $filter);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }
```
