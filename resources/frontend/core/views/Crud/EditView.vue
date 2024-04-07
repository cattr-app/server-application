<template>
    <div class="container crud">
        <div class="at-container crud__content crud__edit-view">
            <div class="page-controls">
                <h1 class="control-item title">
                    {{ $route.params.id ? `${$t(pageData.title)} #${$route.params.id}` : `${$t(pageData.title)}` }}
                </h1>
                <div class="control-items">
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
                            >
                                {{ $t(button.label) }}
                            </at-button>
                        </template>
                    </template>
                    <at-button size="large" class="control-item" @click="$router.go(-1)"
                        >{{ $t('control.back') }}
                    </at-button>
                </div>
            </div>

            <component
                :is="component"
                v-for="(component, index) of pageData.topComponents"
                :key="index"
                :parent="this"
            ></component>
            <validation-observer ref="form" v-slot="{ invalid }">
                <div class="data-entries">
                    <template v-for="(field, key) of fields">
                        <template v-if="isDisplayable(field)">
                            <div :key="key" class="data-entry">
                                <div class="row">
                                    <div class="col-6">
                                        <at-tooltip
                                            v-if="field.tooltipValue"
                                            :content="$t(field.tooltipValue)"
                                            placement="top-left"
                                        >
                                            <u class="label label-tooltip">
                                                {{ $t(field.label) }}
                                                <span v-if="field.required">*</span>
                                            </u>
                                        </at-tooltip>
                                        <p v-else class="label">
                                            {{ $t(field.label) }}
                                            <span v-if="field.required">*</span>
                                        </p>
                                    </div>
                                    <at-input
                                        v-if="isDataLoading && pageData.type === 'edit'"
                                        class="col-18"
                                        disabled
                                    />
                                    <div v-else class="col-18">
                                        <validation-provider
                                            v-if="typeof field.render !== 'undefined'"
                                            v-slot="{ errors }"
                                            :rules="
                                                typeof field.rules === 'string'
                                                    ? field.rules
                                                    : field.required
                                                      ? 'required'
                                                      : ''
                                            "
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <renderable-field
                                                v-model="values[field.key]"
                                                :render="field.render"
                                                :field="field"
                                                :values="values"
                                                :setValue="setValue"
                                                :class="{
                                                    'at-select--error at-input--error has-error': errors.length > 0,
                                                }"
                                            />
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.key === 'email'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required|email' : ''"
                                            :name="field.key"
                                            :vid="field.key"
                                        >
                                            <at-input
                                                v-model="values[field.key]"
                                                :placeholder="$t(field.placeholder) || ''"
                                                :type="field.frontendType || ''"
                                                :status="errors.length > 0 ? 'error' : ''"
                                            ></at-input>
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'input' || field.type === 'text'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <at-input
                                                v-model="values[field.key]"
                                                :placeholder="$t(field.placeholder) || ''"
                                                :type="field.frontendType || ''"
                                                :status="errors.length > 0 ? 'error' : ''"
                                            ></at-input>
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'number'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <at-input-number
                                                v-model="values[field.key]"
                                                :min="field.minValue"
                                                :max="field.maxValue"
                                            ></at-input-number>
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'select'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <at-select
                                                v-model="values[field.key]"
                                                :class="{
                                                    'at-select--error': errors.length > 0,
                                                }"
                                                :placeholder="$t('control.select')"
                                            >
                                                <at-option
                                                    v-for="(option, optionKey) of field.options"
                                                    :key="optionKey"
                                                    :value="option.value"
                                                    >{{ ucfirst($t(option.label)) }}
                                                </at-option>
                                            </at-select>
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'checkbox'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <at-checkbox v-model="values[field.key]" label="" />
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'resource-select'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <resource-select
                                                v-model="values[field.key]"
                                                :service="field.service"
                                                :class="{
                                                    'at-select--error': errors.length > 0,
                                                }"
                                            />
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>

                                        <validation-provider
                                            v-else-if="field.type === 'textarea'"
                                            v-slot="{ errors }"
                                            :rules="field.required ? 'required' : ''"
                                            :name="$t(field.label)"
                                            :vid="field.key"
                                        >
                                            <at-textarea
                                                v-model="values[field.key]"
                                                autosize
                                                min-rows="2"
                                                :class="{
                                                    'at-textarea--error': errors.length > 0,
                                                }"
                                                :placeholder="$t(field.placeholder) || ''"
                                            />
                                            <small>{{ errors[0] }}</small>
                                        </validation-provider>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </template>
                </div>
                <component
                    :is="component"
                    v-for="(component, index) of pageData.bottomComponents"
                    :key="index"
                    :parent="this"
                />
                <at-button type="primary" :disabled="invalid || isLoading" :loading="isLoading" @click="submit">{{
                    $t('control.save')
                }}</at-button>
            </validation-observer>
        </div>
    </div>
</template>

<script>
    import RenderableField from '@/components/RenderableField';
    import ResourceSelect from '@/components/ResourceSelect';
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import { ucfirst } from '@/utils/string';

    export default {
        name: 'EditView',
        components: {
            RenderableField,
            ResourceSelect,
            ValidationProvider,
            ValidationObserver,
        },

        data() {
            const meta = this.$route.meta;
            const pageData = meta.pageData || {};

            return {
                service: meta.service,
                fields: meta.fields || [],
                values: {},
                filters: this.$route.meta.filters,

                pageData: {
                    title: pageData.title,
                    topComponents: pageData.topComponents || [],
                    bottomComponents: pageData.bottomComponents || [],
                    type: pageData.type || 'new',
                    routeNamedSection: pageData.editRouteName || '',
                    pageControls: this.$route.meta.pageData.pageControls || [],
                    editRouteName: pageData.editRouteName || '',
                },

                isLoading: false,
                isDataLoading: false,
                afterSubmitCallback: meta.afterSubmitCallback,
            };
        },

        async mounted() {
            if (!Object.values(this.values).length) {
                await this.fetchData();
            }
        },

        async beforeRouteEnter(to, from, next) {
            next(async vm => {
                await vm.fetchData();
                next();
            });
        },

        async beforeRouteUpdate(to, from, next) {
            await this.fetchData();
            next();
        },

        methods: {
            ucfirst,
            async fetchData() {
                this.isDataLoading = true;

                if (this.pageData.type === 'edit') {
                    try {
                        const { data } = await this.service.getItem(
                            this.$route.params[this.service.getIdParam()],
                            this.filters,
                        );
                        this.values = { ...this.values, ...data.data };
                    } catch ({ response }) {
                        if (
                            response &&
                            Object.prototype.hasOwnProperty.call(response, 'data') &&
                            response.data.error_type === 'query.item_not_found'
                        ) {
                            this.$router.replace({ name: 'forbidden' });
                        }
                    }
                } else if (this.pageData.type === 'new') {
                    this.fields.forEach(field => {
                        if (field.default !== undefined) {
                            this.values[field.key] =
                                typeof field.default === 'function' ? field.default(this.$store) : field.default;
                        }
                    });
                }

                this.isDataLoading = false;
            },

            async submit() {
                const valid = await this.$refs.form.validate();
                if (!valid) {
                    return;
                }

                this.isLoading = true;
                try {
                    const data = (await this.service.save(this.values, this.pageData.type === 'new')).data;
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.record.save.success.title'),
                        message: this.$t('notification.record.save.success.message'),
                    });

                    this.isLoading = false;

                    if (this.afterSubmitCallback) {
                        this.afterSubmitCallback();
                    } else if (this.pageData.type === 'new') {
                        this.$router.push({
                            name: this.$route.meta.navigation.view,
                            params: { id: data.res[this.service.getIdParam()] },
                        });
                    }
                } catch ({ response }) {
                    this.isLoading = false;
                    this.$refs.form.setErrors(response.data.info);
                }
            },

            handleClick(button) {
                button.onClick(this, this.values[this.service.getIdParam()]);
            },

            checkRenderCondition(button) {
                return typeof button.renderCondition !== 'undefined' ? button.renderCondition(this) : true;
            },

            setValue(key, value) {
                this.$set(this.values, key, value);
            },

            isDisplayable(field) {
                if (typeof field.displayable === 'function') {
                    return field.displayable(this);
                }

                if (typeof field.displayable !== 'undefined') {
                    return !!field.displayable;
                }

                return true;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .crud {
        &__edit-view {
            .page-controls {
                margin-bottom: 1.5em;
                display: flex;
                justify-content: space-between;

                .control-item {
                    margin-right: 0.5em;

                    &:last-child {
                        margin-right: 0;
                    }
                }

                .title {
                    margin-right: 1.5em;
                    font-size: 1.6rem;
                }
            }

            .data-entries {
                .data-entry {
                    margin-bottom: $layout-02;

                    .label {
                        font-weight: bold;
                    }
                }
            }
        }
    }
</style>
