<template>
    <div>
        <transition appear mode="out-in" name="fade">
            <!-- lazy mode: reserve the thumbnail box, so only images near the viewport get loaded -->
            <div v-if="!loaded && lazy" class="app-image__placeholder">
                <Skeleton height="100%" />
            </div>
            <Skeleton v-else-if="!loaded" />
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
                    <div v-else-if="lazy">
                        <img :src="url" alt="screenshot" @click="$emit('click', $event)" @error="handleError" />
                    </div>
                    <img v-else :src="url" alt="screenshot" @click="$emit('click', $event)" @error="handleError" />
                </component>
            </template>
        </transition>
    </div>
</template>

<script>
    import Skeleton from '@/components/Skeleton';
    import { isUrlExpired } from '@/utils/screenshotUrl';

    export default {
        name: 'AppImage',
        props: {
            // Ready URL for <img src>; "none" shows the error placeholder
            src: {
                type: String,
                default: null,
            },
            // Async URL provider, used instead of src; called again once when a temporary URL has expired.
            // With `lazy`, it is called only when the image comes near the viewport.
            resolver: {
                type: Function,
                default: null,
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
            return {
                error: false,
                loaded: false,
                url: null,
                retried: false,
                loadId: 0,
                // lazy mode: the image has come near the viewport
                visible: false,
            };
        },
        components: {
            Skeleton,
        },
        methods: {
            async load() {
                const loadId = ++this.loadId;

                this.error = false;
                this.loaded = false;
                this.url = null;

                let url = null;
                try {
                    url = this.resolver ? await this.resolver() : this.src;
                } catch (e) {
                    url = null;
                }

                // a newer load (src / resolver changed) took over meanwhile
                if (loadId !== this.loadId) {
                    return;
                }

                if (!url || url === 'none') {
                    this.error = true;
                } else {
                    this.url = url;
                }
                this.loaded = true;
            },
            reload() {
                this.retried = false;
                // lazy mode: the new source is loaded when the image becomes visible
                if (this.lazy && !this.visible) {
                    return;
                }
                this.load();
            },
            // Loads the image once it comes near the viewport (within 1.3 of its size, as vue-lazyload did)
            observe() {
                if (typeof IntersectionObserver === 'undefined') {
                    this.visible = true;
                    this.load();
                    return;
                }

                this.observer = new IntersectionObserver(
                    entries => {
                        if (entries.some(entry => entry.isIntersecting)) {
                            this.disconnect();
                            this.visible = true;
                            this.load();
                        }
                    },
                    { rootMargin: '0px 30% 30% 0px' },
                );
                this.observer.observe(this.$el);
            },
            disconnect() {
                if (this.observer) {
                    this.observer.disconnect();
                    this.observer = null;
                }
            },
            handleError() {
                // the page may stay open longer than the URL lifetime: get a fresh URL once
                if (this.resolver && !this.retried && isUrlExpired(this.url)) {
                    this.retried = true;
                    this.load();
                    return;
                }

                this.error = true;
            },
        },
        mounted() {
            if (this.lazy) {
                this.observe();
            } else {
                this.load();
            }
        },
        beforeDestroy() {
            this.disconnect();
        },
        watch: {
            src() {
                this.reload();
            },
            resolver() {
                this.reload();
            },
        },
    };
</script>

<style lang="scss" scoped>
    // Proportions of a screenshot thumbnail (280 px wide), as in the error placeholder below
    .app-image__placeholder {
        aspect-ratio: 280 / 162;
        line-height: 0;
    }

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
    .fade-enter-from,
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
