# Routing

- - -

To work with routing we use the official library Vue Router. More info on it can be found here: <https://router.vuejs.org>.

## Add new route

`addRoute` method receives Vue Router object config as a param.
Any properties that are described in the Vue Router's documentation available.

```javascript
context.addRoute({
    path: '/users',
    name: 'users',
    component: () => import('./views/Users.vue')
});
```

?> Unauthorized users by default don't have access to the route. More info on that is available on [Route guard](/en/api/context/routing/guard/?id=Authorized-users) page.
