<template>
    <li
        class="at-menu__item"
        :class="[this.active ? 'at-menu__item--active' : '', this.disabled ? 'at-menu__item--disabled' : '']"
    >
        <router-link v-if="Object.keys(to).length" ref="link" class="at-menu__item-link" :to="to">
            <slot></slot>
        </router-link>
        <div v-else class="at-menu__item-link">
            <slot></slot>
        </div>
    </li>
</template>

<script>
    import { findComponentsUpward } from '@cattr/ui-kit/src/utils/util';

    export default {
        name: 'NavigationMenuItem',
        props: {
            name: {
                type: [String, Number],
            },
            to: {
                type: [Object, String],
                default() {
                    return {};
                },
            },
            replace: {
                type: Boolean,
                default: false,
            },
            disabled: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            return {
                active: false,
            };
        },
        mounted() {
            this.$on('on-update-active', name => {
                this.$nextTick(() => {
                    if (
                        this.name === name ||
                        (this.$refs.link && this.$refs.link.$el.classList.contains('router-link-active'))
                    ) {
                        this.active = true;

                        const parents = findComponentsUpward(this, 'AtSubmenu');
                        if (parents && parents.length) {
                            parents.forEach(parent => {
                                parent.$emit('on-update-active', true);
                            });
                        }
                    } else {
                        this.active = false;
                    }
                });
            });
        },
    };
</script>
