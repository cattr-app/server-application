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
            <at-option v-for="option of options" :key="option.id" :label="option.name" :value="option.id" />
        </at-select>
        <at-input v-else disabled></at-input>
    </div>
</template>

<script>
    import GanttService from '@/services/resource/gantt.service';

    export default {
        name: 'PhaseSelect',
        props: {
            value: {
                type: [String, Number],
                default: '',
            },
            projectId: {
                type: Number,
            },
            clearable: {
                type: Boolean,
                default: () => false,
            },
        },
        created() {
            this.loadOptions();
        },
        data() {
            return {
                options: [],
            };
        },
        methods: {
            async loadOptions() {
                if (this.projectId === 0) {
                    this.options = [];
                    return;
                }
                try {
                    this.options = (await new GanttService().getPhases(this.projectId)).data.data.phases;
                    await this.$nextTick();

                    if (this.$refs.select && Object.prototype.hasOwnProperty.call(this.$refs.select, '$children')) {
                        this.$refs.select.$children.forEach(option => {
                            option.hidden = false;
                        });
                    }
                } catch ({ response }) {
                    this.options = [];
                    if (process.env.NODE_ENV === 'development') {
                        console.warn(response ? response : 'request to resource is canceled');
                    }
                }
            },
        },
        watch: {
            projectId() {
                this.loadOptions();
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
