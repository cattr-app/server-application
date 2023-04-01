<template>
    <div>
        <at-select
            v-if="options.length"
            ref="select"
            v-model="model"
            :placeholder="$t('control.select')"
            filterable
            clearable="clearable"
        >
            <at-option v-for="option of options" :key="option.id" :label="formattedLabel(option)" :value="option.id" />
        </at-select>
        <at-input v-else disabled></at-input>
    </div>
</template>

<script>
    import { ucfirst } from '@/utils/string';

    export default {
        name: 'ResourceSelect',
        props: {
            value: {
                type: [String, Number],
                default: '',
            },
            service: {
                type: Object,
            },
            clearable: {
                type: Boolean,
                default: () => false,
            },
        },
        async created() {
            try {
                this.options = await this.service.getAll({ headers: { 'X-Paginate': 'false' } });

                await this.$nextTick();

                if (this.$refs.select && Object.prototype.hasOwnProperty.call(this.$refs.select, '$children')) {
                    this.$refs.select.$children.forEach(option => {
                        option.hidden = false;
                    });
                }
            } catch ({ response }) {
                if (process.env.NODE_ENV === 'development') {
                    console.warn(response ? response : 'request to resource is canceled');
                }
            }
        },
        data() {
            return {
                options: [],
            };
        },
        methods: {
            ucfirst,
            getName(object = {}) {
                const names = ['full_name'];
                let key = 'name';

                if (typeof object === 'object') {
                    let keys = Object.keys(object);

                    for (let i = 0; i <= names.length; i++) {
                        if (keys.indexOf(names[i]) !== -1) {
                            key = names[i];
                            break;
                        }
                    }
                    return object[key] ?? '';
                }
            },
            formattedLabel(option) {
                const name = this.getName(option);
                return name ? this.ucfirst(name) : '';
            },
        },
        computed: {
            model: {
                get() {
                    return this.value;
                },
                set(value) {
                    this.$emit('input', value);
                },
            },
        },
    };
</script>
