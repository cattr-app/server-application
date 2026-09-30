# Route guard

- - -

Route guard lets you limit routes access depending on according conditions, e.g. only authorized users with the specific rule set can view this page.

## Authorized users

By default unauthorized users don't have the route access.
To allow unauthorized users the route access, add the `auth` field with the `false` value in the Vue Router object config's `meta` property.

```javascript
context.addRoute({
    path: '/users',
    name: 'users',
    meta: {
    	auth: false
    },
    component: () => import('./views/Users.vue')
});
```

## Rules

To limit users the route access based on the specific ruleset, add the `permissions` field with the rules array in the Vue Router object config's `meta` property.

```javascript
context.addRoute({
    path: '/users',
    name: 'users',
    meta: {
    	permissions: ['users/create', 'users/list']
    },
    component: () => import('./views/Users.vue')
});
```

?>If the user doesn't have at least one of the rules mentioned, the route access will be limited for its account.
