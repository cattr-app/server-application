<template>
    <at-button v-bind="$attrs" v-on="$listeners" @click="onClick"><slot /></at-button>
</template>

<script>
    export default {
        data() {
            return {
                routeChanged: true,
            };
        },

        beforeDestroy() {
            this.routeChanged = true;
        },

        methods: {
            onClick() {
                this.routeChanged = false;
                this.$router.go(-1);

                setTimeout(() => {
                    if (!this.routeChanged && window.opener !== null) {
                        window.opener.focus();
                        window.close();
                    }
                }, 100);
            },
        },

        watch: {
            $route() {
                this.routeChanged = true;
            },
        },
    };
</script>
