<template>
    <div>
        <h1 v-if="this.section" class="page-title settings__title">{{ $t(this.section.label) }}</h1>

        <template v-if="this.section && values">
            <component
                :is="component"
                v-for="(component, index) of this.section.topComponents"
                :key="index"
                :parent="this"
            />
            <validation-observer ref="form">
                <div class="data-entries">
                    <template v-for="(fields, groupKey) of this.groups">
                        <template v-for="(field, key) of fields">
                            <template v-if="typeof field.displayable === 'function' ? field.displayable($store) : true">
                                <div :key="key" class="data-entry">
                                    <div class="row">
                                        <div class="col-6 label">
                                            <at-tooltip
                                                v-if="field.tooltipValue"
                                                :content="$t(field.tooltipValue)"
                                                placement="top-right"
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
                                        <div class="col">
                                            <validation-provider
                                                v-if="typeof field.render === 'function'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <renderable-field
                                                    v-model="values[field.key]"
                                                    :render="field.render"
                                                    :field="field"
                                                    :values="values"
                                                    class="with-margin"
                                                    :class="{
                                                        'at-select--error at-input--error has-error': errors.length > 0,
                                                    }"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="
                                                    field.fieldOptions.type === 'input' ||
                                                    field.fieldOptions.type === 'text'
                                                "
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <at-input
                                                    v-model="values[field.key]"
                                                    :readonly="field.fieldOptions.disableAutocomplete || false"
                                                    :placeholder="$t(field.fieldOptions.placeholder) || ''"
                                                    :type="field.fieldOptions.frontendType || ''"
                                                    :status="errors.length > 0 ? 'error' : ''"
                                                    @focus="removeReadonly"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'number'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <at-input-number
                                                    v-model="values[field.key]"
                                                    :min="field.minValue"
                                                    :max="field.maxValue"
                                                    size="large"
                                                    @blur="handleInputNumber($event, field.key)"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'select'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <at-select v-model="values[field.key]" class="with-margin">
                                                    <at-option
                                                        v-for="(option, optionKey) of getSelectOptions(field, values)"
                                                        :key="optionKey"
                                                        :value="option.value"
                                                        >{{ $t(option.label) }}
                                                    </at-option>
                                                </at-select>
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'textarea'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <at-textarea
                                                    v-model="values[field.key]"
                                                    autosize
                                                    class="with-margin"
                                                    :class="{
                                                        'at-textarea--error': errors.length > 0,
                                                    }"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'listbox'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <ListBox
                                                    v-model="values[field.key]"
                                                    :keyField="field.fieldOptions.keyField"
                                                    :labelField="field.fieldOptions.labelField"
                                                    :valueField="field.fieldOptions.valueField"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'checkbox'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <at-checkbox v-model="values[field.key]" label="" />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>

                                            <validation-provider
                                                v-else-if="field.fieldOptions.type === 'switch'"
                                                v-slot="{ errors }"
                                                :rules="field.rules || ''"
                                                :name="$t(field.label)"
                                                :vid="field.key"
                                            >
                                                <span
                                                    v-if="field.fieldOptions.checkedText"
                                                    v-html="field.fieldOptions.checkedText"
                                                />
                                                <at-switch
                                                    v-model="values[field.key]"
                                                    size="large"
                                                    @change="$set(values, field.key, $event)"
                                                >
                                                    <template
                                                        v-if="field.fieldOptions.innerCheckedText"
                                                        v-slot:checkedText
                                                    >
                                                        <span v-html="field.fieldOptions.innerCheckedText" />
                                                    </template>
                                                    <template
                                                        v-if="field.fieldOptions.innerUnCheckedText"
                                                        v-slot:unCheckedText
                                                    >
                                                        <span v-html="field.fieldOptions.innerUnCheckedText" />
                                                    </template>
                                                </at-switch>
                                                <span
                                                    v-if="field.fieldOptions.unCheckedText"
                                                    v-html="field.fieldOptions.unCheckedText"
                                                />
                                                <small>{{ errors[0] }}</small>
                                            </validation-provider>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </template>

                        <hr :key="groupKey" class="group-divider" />
                    </template>
                </div>
                <component
                    :is="component"
                    v-for="(component, index) of this.section.bottomComponents"
                    :key="index"
                    :parent="this"
                ></component>
                <at-button type="primary" :loading="isLoading" :disabled="isLoading" @click="submit"
                    >{{ $t('control.save') }}
                </at-button>
            </validation-observer>
        </template>
    </div>
</template>

<script>
    import ListBox from '@/components/ListBox';
    import RenderableField from '@/components/RenderableField';
    import { ValidationObserver, ValidationProvider } from 'vee-validate';

    export default {
        name: 'DynamicSettings',

        components: {
            RenderableField,
            ListBox,
            ValidationObserver,
            ValidationProvider,
        },

        data() {
            return {
                section: {},
                values: {},
                isLoading: false,
            };
        },

        mounted() {
            this.fetchSectionData();
        },

        watch: {
            sections() {
                this.fetchSectionData();
            },
        },

        computed: {
            sections() {
                return this.$store.getters['settings/sections'];
            },

            groups() {
                if (!this.section) {
                    return {};
                }

                const { fields } = this.section;
                if (!fields) {
                    return {};
                }

                return Object.keys(fields)
                    .map(key => ({ key, field: fields[key] }))
                    .reduce((groups, { key, field }) => {
                        const groupKey = field.group || 'default';
                        if (!groups[groupKey]) {
                            groups[groupKey] = {};
                        }

                        groups[groupKey][key] = field;
                        return groups;
                    }, {});
            },
        },

        methods: {
            handleInputNumber(ev, key) {
                let number = ev.target.valueAsNumber;
                if (ev.target.max && number > ev.target.max) {
                    number = Number(ev.target.max);
                    ev.target.valueAsNumber = number;
                    ev.target.value = String(number);
                }
                if (ev.target.min && number < ev.target.min) {
                    number = Number(ev.target.min);
                    ev.target.valueAsNumber = number;
                    ev.target.value = String(number);
                }

                this.values[key] = number;
            },
            fetchSectionData() {
                const name = this.$route.name;
                this.section = this.$store.getters['settings/sections'].find(s => s.pathName === name);

                if (this.section) {
                    this.values = { ...this.values, ...this.section.data };
                }
            },
            removeReadonly(el) {
                if (el.target.getAttribute('readonly') === 'readonly') {
                    el.target.removeAttribute('readonly');
                }
            },
            getSelectOptions(field, values) {
                const { options } = field.fieldOptions;

                if (typeof options === 'function') {
                    return options({ field, values });
                }

                return options;
            },
            async submit() {
                const valid = await this.$refs.form.validate();
                if (!valid) {
                    return;
                }

                this.isLoading = true;

                try {
                    await this.section.service.save(this.values);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.settings.save.success.title'),
                        message: this.$t('notification.settings.save.success.message'),
                    });
                } catch ({ response }) {
                    if (
                        typeof response !== 'undefined' &&
                        Object.prototype.hasOwnProperty.call(response, 'data') &&
                        Object.prototype.hasOwnProperty.call(response.data, 'info')
                    ) {
                        this.$refs.form.setErrors(response.data.info);
                    }
                } finally {
                    this.isLoading = false;
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .settings {
        &__title {
            font-size: 24px;
        }

        &__content {
            width: 100%;

            .data-entry {
                margin-bottom: 1em;
            }

            .label {
                font-weight: bold;
            }
        }
    }

    .group-divider {
        border: 0;
        border-top: 1px solid #eeeef5;

        &:last-child {
            display: none;
        }
    }
</style>
