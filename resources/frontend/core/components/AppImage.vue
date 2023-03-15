<template>
    <div>
        <transition appear mode="out-in" name="fade">
            <Skeleton v-if="!loaded" />
            <template v-else>
                <component :is="openable ? 'a' : 'div'" :href="url" target="_blank">
                    <svg
                        v-if="error"
                        class="error-image"
                        viewBox="0 0 280 162"
                        x="0px"
                        xml:space="preserve"
                        xmlns="http://www.w3.org/2000/svg"
                        xmlns:xlink="http://www.w3.org/1999/xlink"
                        y="0px"
                    >
                        <rect height="162" width="280" />
                        <path
                            d="M140,30.59c-27.85,0-50.41,22.56-50.41,50.41s22.56,50.41,50.41,50.41s50.41-22.56,50.41-50.41
                            S167.85,30.59,140,30.59z M140,121.65c-22.42,0-40.65-18.23-40.65-40.65S117.58,40.35,140,40.35S180.65,58.58,180.65,81
                            S162.42,121.65,140,121.65z M123.74,77.75c3.6,0,6.5-2.91,6.5-6.5s-2.91-6.5-6.5-6.5s-6.5,2.91-6.5,6.5S120.14,77.75,123.74,77.75z
                            M156.26,64.74c-3.6,0-6.5,2.91-6.5,6.5s2.91,6.5,6.5,6.5c3.6,0,6.5-2.91,6.5-6.5S159.86,64.74,156.26,64.74z M140,90.76
                            c-8.17,0-15.85,3.6-21.1,9.88c-1.73,2.07-1.44,5.14,0.63,6.87c2.07,1.71,5.14,1.44,6.87-0.63c3.37-4.05,8.33-6.38,13.6-6.38
                            s10.22,2.32,13.6,6.38c1.65,1.97,4.7,2.42,6.87,0.63c2.07-1.73,2.34-4.8,0.63-6.87C155.85,94.35,148.17,90.76,140,90.76z"
                        />
                    </svg>
                    <lazy-component v-else-if="lazy">
                        <img :src="url" alt="screenshot" @click="$emit('click', $event)" @error="handleError" />
                    </lazy-component>
                    <img v-else :src="url" alt="screenshot" @click="$emit('click', $event)" @error="handleError" />
                </component>
            </template>
        </transition>
    </div>
</template>

<script>
    import axios from '@/config/app';
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        name: 'AppImage',
        props: {
            src: {
                type: String,
                required: true,
            },
            lazy: {
                type: Boolean,
                default: false,
            },
            openable: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            const baseUrl =
                this.src.indexOf('http') === 0
                    ? ''
                    : (process.env.VUE_APP_API_URL !== 'null'
                          ? process.env.VUE_APP_API_URL
                          : `${window.location.origin}/api`) + '/';

            const url = baseUrl + this.src;

            return {
                error: this.src === 'none',
                loaded: this.src === 'none',
                url,
                baseUrl,
            };
        },
        components: {
            Skeleton,
        },
        methods: {
            load() {
                if (this.error) return;

                if (this.src === 'none') {
                    this.error = true;
                    return;
                }

                this.loaded = false;

                if (this.url) {
                    URL.revokeObjectURL(this.url);
                    this.url = null;
                }

                if (this.src) {
                    axios
                        .get(this.src, {
                            responseType: 'blob',
                            muteError: true,
                        })
                        .then(({ data }) => {
                            this.url = URL.createObjectURL(data);
                        })
                        .catch(() => {
                            this.error = true;
                        })
                        .finally(() => {
                            this.loaded = true;
                        });
                }
            },
            handleError() {
                this.error = true;
            },
        },
        mounted() {
            this.load();
        },
        beforeDestroy() {
            if (this.url) {
                URL.revokeObjectURL(this.url);
                this.url = null;
            }
        },
        watch: {
            src() {
                this.error = false;
                this.load();
            },
        },
    };
</script>

<style lang="scss" scoped>
    img {
        width: 100%;
        object-fit: cover;
        background-color: $gray-5;
    }

    .fade-enter-active,
    .fade-leave-active {
        transition: opacity 0.4s;
    }

    .fade-enter,
    .fade-leave-to {
        opacity: 0;
    }

    .error-image {
        rect {
            fill: $gray-4;
        }

        path {
            fill: $red-1;
        }
    }
</style>
