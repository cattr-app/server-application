<template>
    <div class="container-fluid crud">
        <div class="row flex-around">
            <div class="col-24 col-sm-22 col-lg-20">
                <div class="at-container crud__content crud__item-view">
                    <div class="page-controls">
                        <h1 class="control-item title">
                            <Skeleton :loading="isDataLoading" width="200px">{{ title }}</Skeleton>
                        </h1>

                        <div class="control-items">
                            <at-button
                                size="large"
                                class="control-item"
                                @click="$router.go($route.meta.navigation.from)"
                                >{{ $t('control.back') }}
                            </at-button>
                            <template v-if="pageData.pageControls && pageData.pageControls.length > 0">
                                <template v-for="(button, key) of pageData.pageControls">
                                    <at-button
                                        v-if="checkRenderCondition(button)"
                                        :key="key"
                                        class="control-item"
                                        size="large"
                                        :type="button.renderType || ''"
                                        :icon="button.icon || ''"
                                        @click="handleClick(button)"
                                        >{{ $t(button.label) }}
                                    </at-button>
                                </template>
                            </template>
                        </div>
                    </div>
                    <component
                        :is="component"
                        v-for="(component, index) of pageData.topComponents"
                        :key="index"
                        :parent="this"
                    ></component>
                    <div class="data-entries">
                        <div v-for="(field, key) of fields" v-bind:key="key" class="data-entry">
                            <div class="row">
                                <div class="col-6 col-xs-24 label">{{ $t(field.label) }}:</div>
                                <div class="col">
                                    <Skeleton :loading="isDataLoading">
                                        <renderable-field
                                            v-if="typeof field.render !== 'undefined' && Object.keys(values).length > 0"
                                            :render="field.render"
                                            :value="values[field.key]"
                                            :field="field"
                                            :values="values"
                                        ></renderable-field>
                                        <template v-else>{{ values[field.key] }}</template>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                    </div>
                    <component
                        v-bind:is="component"
                        v-for="(component, index) of pageData.bottomComponents"
                        v-bind:key="index"
                        :parent="this"
                    ></component>
                </div>
            </div>
            <!-- /.col-24 -->
        </div>
        <!-- /.row -->
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import RenderableField from '@/components/RenderableField';
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        name: 'ItemView',

        components: {
            RenderableField,
            Skeleton,
        },

        provide() {
            return {
                reload: this.load,
            };
        },

        computed: {
            ...mapGetters('user', ['user']),
            title() {
                const { fields, values, service, filters, pageData } = this;
                const { titleCallback } = this.$route.meta;
                if (typeof titleCallback === 'function') {
                    return titleCallback({ fields, values, service, filters, pageData });
                }

                return this.$t(pageData.title);
            },
        },

        data() {
            const { fields, service, filters, pageData } = this.$route.meta;

            return {
                service,
                filters,
                values: {},
                fields: fields || [],
                isDataLoading: false,
                pageData: {
                    title: pageData.title || null,
                    topComponents: pageData.topComponents || [],
                    bottomComponents: pageData.bottomComponents || [],
                    pageControls: pageData.pageControls || [],
                },
            };
        },

        async mounted() {
            await this.load();

            this.websocketEnterChannel = this.$route.meta.pageData.websocketEnterChannel;
            this.websocketLeaveChannel = this.$route.meta.pageData.websocketLeaveChannel;

            if (typeof this.websocketEnterChannel !== 'undefined') {
                this.websocketEnterChannel(this.user.id, {
                    edit: data => {
                        const id = this.$route.params[this.service.getIdParam()];
                        if (+id === +data.model.id) {
                            this.values = data.model;
                        }
                    },
                });
            }
        },

        beforeRouteEnter(to, from, next) {
            if ('pageData' in from.meta && from.meta.pageData.type === 'new') {
                to.meta.navigation.from = -2;
            } else {
                to.meta.navigation.from = -1;
            }

            next();
        },

        beforeDestroy() {
            if (typeof this.websocketLeaveChannel !== 'undefined') {
                this.websocketLeaveChannel(this.user.id);
            }
        },

        methods: {
            async load() {
                this.isDataLoading = true;
                const id = this.$route.params[this.service.getIdParam()];

                try {
                    const { data } = (await this.service.getItem(id, this.filters)).data;
                    this.values = data;
                } catch ({ response }) {
                    if (response.data.error_type === 'query.item_not_found') {
                        this.$router.replace({ name: 'forbidden' });
                    }
                }

                this.isDataLoading = false;
            },

            handleClick(button) {
                button.onClick(this, this.values[this.service.getIdParam()]);
            },

            checkRenderCondition(button) {
                return typeof button.renderCondition !== 'undefined' ? button.renderCondition(this) : true;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .crud {
        &__item-view {
            .page-controls {
                margin-bottom: 1.5em;
                display: flex;
                justify-content: space-between;
                align-items: flex-start;

                .control-items {
                    gap: 1em;
                }

                .control-item {
                    &:last-child {
                        margin-right: 0;
                    }
                }

                .title {
                    font-size: 1.6rem;
                    @media (max-width: 768px) {
                        font-size: 1rem;
                    }
                }
            }

            .data-entries {
                .data-entry {
                    padding-bottom: 1em;
                    margin-bottom: 1em;
                    border-bottom: 1px solid $gray-6;

                    &:last-child {
                        border-bottom: none;
                    }

                    .label {
                        margin-right: 1em;
                        font-weight: bold;
                        @media (max-width: 768px) {
                            font-size: 0.8rem;
                            width: 100%;
                        }
                    }
                }
            }
        }
    }
</style>
