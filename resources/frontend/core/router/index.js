import Vue from 'vue';
import VueRouter from 'vue-router';
import Store from '@/store';

// Fixing new issue with VueRouter caused by new Promise API
const originalPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location) {
    return originalPush.call(this, location).catch(err => err);
};

Vue.use(VueRouter);

const routes = [
    {
        path: '/auth/login',
        name: 'auth.login',
        meta: {
            auth: false,
            guest: true,
            layout: 'auth-layout',
        },
        component: () => import(/* webpackChunkName: "login" */ '@/views/Auth/Login.vue'),
        beforeEnter: (to, from, next) => {
            if (Store.getters['user/loggedIn']) {
                next('/');
            } else {
                next();
            }
        },
    },
    {
        path: '/auth/desktop/login',
        name: 'auth.desktop.login',
        meta: {
            auth: false,
            guest: true,
            layout: 'auth-layout',
        },
        beforeEnter: (to, from, next) => {
            if (Store.getters['user/loggedIn']) {
                next('/');
            } else {
                next();
            }
        },
        component: () => import(/* webpackChunkName: "desktop-login" */ '@/views/Auth/Desktop.vue'),
    },
    {
        path: '/auth/password/reset',
        name: 'auth.password.reset',
        meta: {
            auth: false,
        },
        component: () => import(/* webpackChunkName: "ResetPassword" */ '@/views/Auth/ResetPassword.vue'),
    },
    {
        path: '/auth/register',
        name: 'auth.register',
        meta: {
            auth: false,
        },
        component: () => import(/* webpackChunkName: "Register" */ '@/views/Auth/Register.vue'),
        beforeEnter: (to, from, next) => {
            if (Store.getters['user/loggedIn']) {
                Store.dispatch('user/forceUserExit');
            }

            next();
        },
    },
    {
        path: '*',
        name: 'not-found',
        meta: {
            auth: false,
        },
        component: () => import(/* webpackChunkName: "PageNotFound" */ '@/views/PageNotFound.vue'),
    },
    {
        path: '/error',
        name: 'api.error',
        meta: {
            auth: false,
        },
        component: () => import(/* webpackChunkName: "ApiError" */ '@/views/ApiError.vue'),
    },
    {
        path: '/forbidden',
        name: 'forbidden',
        meta: {
            auth: false,
        },
        component: () => import(/* webpackChunkName: "PageForbidden" */ '@/views/PageForbidden.vue'),
    },
    {
        path: '/about',
        name: 'about',
        component: () => import(/* webpackChunkName: "About" */ '@/views/About.vue'),
    },
    {
        path: '/desktop-login',
        name: 'desktop-login',
        component: () => import(/* webpackChunkName: "DesktopLogin" */ '@/views/DesktopLogin.vue'),
    },
];

const router = new VueRouter({
    mode: 'history',
    base: process.env.BASE_URL,
    routes,
});

router.beforeEach(async (to, from, next) => {
    // Close pending requests when switching pages
    await Store.dispatch('httpRequest/cancelPendingRequests');

    if (to.matched.some(record => record.meta.auth || typeof record.meta.auth === 'undefined')) {
        if (!Store.getters['user/loggedIn']) {
            return next({ name: 'auth.login' });
        }
    } else if (to.matched.some(record => !record.meta.auth) && !Store.getters['user/loggedIn']) {
        return next();
    }

    if (to.name === 'setup') {
        if (Store.getters['httpRequest/getStatusOfInstalling']) {
            next({ name: 'forbidden' });
        }

        return next();
    }

    if (to.matched.some(record => typeof record.meta.guest !== 'undefined' && record.meta.guest)) {
        if (Store.getters['user/loggedIn']) {
            return next('/');
        }
    }

    const checkPermission = () => {
        if (!Vue.prototype.$gate.user) {
            Vue.prototype.$gate.auth(Store.getters['user/user']);
        }

        if (to?.meta?.checkPermission) {
            return to.meta.checkPermission() ? next() : next({ name: 'forbidden' });
        }

        return next();
    };

    if (!Store.getters['user/user'] || !Object.keys(Store.getters['user/user']).length) {
        Store.watch(
            () => Store.getters['user/user'],
            () => {
                checkPermission();
            },
        );
    } else {
        checkPermission();
    }
});

export default router;
