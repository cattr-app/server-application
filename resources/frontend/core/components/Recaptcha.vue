<template>
    <div></div>
</template>

<script>
    const SCRIPT_ID = '__RECAPTCHA_SCRIPT';
    const ONLOAD_CALLBACK = 'vueRecaptchaApiLoaded';

    let apiReady = null;

    // Loads the reCAPTCHA API once per page; resolves when grecaptcha.render can be called
    function loadApi() {
        if (apiReady) {
            return apiReady;
        }

        apiReady = new Promise((resolve, reject) => {
            if (window.grecaptcha && window.grecaptcha.render) {
                resolve(window.grecaptcha);
                return;
            }

            window[ONLOAD_CALLBACK] = () => {
                if (window.grecaptcha && window.grecaptcha.render) {
                    resolve(window.grecaptcha);
                } else {
                    apiReady = null;
                    reject(new Error('reCAPTCHA API is not available'));
                }
            };

            // A leftover script has already finished without our callback and would never call it again
            const stale = document.getElementById(SCRIPT_ID);
            if (stale) {
                stale.remove();
            }

            const script = document.createElement('script');
            script.id = SCRIPT_ID;
            script.src = `https://www.google.com/recaptcha/api.js?onload=${ONLOAD_CALLBACK}&render=explicit&hl=`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);

            // do not cache a failed load: the next mount creates a fresh script and retries
            script.addEventListener(
                'error',
                () => {
                    apiReady = null;
                    script.remove();
                    reject(new Error('Failed to load reCAPTCHA'));
                },
                { once: true },
            );
        });

        return apiReady;
    }

    // reCAPTCHA v2 checkbox widget (replaces vue-recaptcha)
    export default {
        name: 'Recaptcha',
        props: {
            sitekey: {
                type: String,
                required: true,
            },
            theme: String,
            size: String,
            tabindex: String,
        },
        data() {
            return {
                widgetId: null,
            };
        },
        created() {
            // plain instance property, not reactive state
            this.destroyed = false;
        },
        async mounted() {
            try {
                const grecaptcha = await loadApi();

                // the form may be gone while the script was loading
                if (this.destroyed) {
                    return;
                }

                this.widgetId = grecaptcha.render(this.$el, {
                    sitekey: this.sitekey,
                    theme: this.theme,
                    size: this.size,
                    tabindex: this.tabindex,
                    callback: response => this.$emit('verify', response),
                    'expired-callback': () => this.$emit('expired'),
                    'error-callback': () => this.$emit('error'),
                });
                this.$emit('render', this.widgetId);
            } catch (e) {
                this.$emit('error', e);
            }
        },
        beforeDestroy() {
            this.destroyed = true;
        },
        methods: {
            reset() {
                if (this.widgetId !== null) {
                    window.grecaptcha.reset(this.widgetId);
                }
            },
        },
    };
</script>
