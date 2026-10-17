# Access policy

User's restrictions in the system are defined by:

- the user's **role_id** flags;
- the HasRole trait;
- the user's default role;
- the user's role in projects.

The HasRole trait checks whether the user is an administrator via the isAdmin () method. If the user is an administrator, they have full access to all system objects.

A role gives the user a set of permissions.

When checking access to objects related to a specific project using getProjectRole, the user's default and project roles are combined. (For example, if a user has the default role of **manager** and the project role of **user**, they have all the rights of both the **user** and **manager** roles over the project's objects.)

Users have unconditional access to:

- viewing tasks and projects assigned to them;
- creating, viewing, and deleting their own screenshots and intervals in assigned projects.

Users can edit their own screenshots and intervals.

## Default Roles

- **user** - can create intervals and screenshots;
- **auditor** - additionally has read access to users, projects, and tasks; can create tasks;
- **manager** - can view, create, and edit users; view, create, edit, and delete projects and tasks.

## Implementation

When processing a request, the system first checks whether the user has access to the endpoint. This is implemented in the hasRoleInAnyProject method, where user roles are cached, and a check is performed to match the passed role:

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

For some permissions (users.list, users.show, users.edit, project.list, project.show, task.list, task.show, screenshots.list, screenshots.show, screenshots.edit, screenshots.remove, time-intervals.list, time-intervals.show, time-intervals.edit, time-intervals.remove), access checks are not done at the endpoint level. For these, objects accessible to the user are filtered at the request level using the ItemController.

---

The controller method getControllerRules () returns an associative array that maps controller actions to the required access permissions. For example, the implementation in TimeController:

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

The user ()->can () method in Laravel checks for the corresponding permission via an authorization policy, determining whether the user has the right to perform a specific action on a given type of object. For example, in the CreateTaskRequest class:

```php
public function authorizeValidated(): bool
    {
        return $this->user()->can('create', [Task::class, $this->get('project_id')]);
    }
```

It checks if the current user (retrieved via the user () method) can create a task in the **Task** model in the current specific project **project_id**

---

In the ItemController, the method `getQuery()` creates a database query for the specified model, applies global scopes, additional relationships, filters, and returns the modified query object.

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
