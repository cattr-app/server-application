import * as Sentry from '@sentry/browser';
import * as Integrations from '@sentry/integrations';
import Vue from 'vue';

if (
    process.env.NODE_ENV !== 'development' &&
    'VUE_APP_SENTRY_DSN' in process.env &&
    process.env.VUE_APP_SENTRY_DSN !== 'undefined'
) {
    Sentry.init({
        release: process.env.VUE_APP_VERSION,
        environment: process.env.NODE_ENV,
        dsn: process.env.VUE_APP_SENTRY_DSN,
        integrations: [
            new Integrations.Vue({
                Vue,
                attachProps: true,
            }),
        ],
    });
}
