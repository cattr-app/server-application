<template>
    <ul class="listbox">
        <li v-for="(value, index) of values" :key="value[keyField]" class="listbox__item">
            <at-checkbox :checked="value[valueField]" @on-change="onChange(index, $event)">
                {{ value[labelField] }}
            </at-checkbox>
        </li>
    </ul>
</template>

<script>
    export default {
        model: {
            prop: 'values',
            event: 'change',
        },

        props: {
            values: {
                type: Array,
                default: () => [],
            },
            keyField: {
                type: String,
                required: true,
            },
            labelField: {
                type: String,
                required: true,
            },
            valueField: {
                type: String,
                required: true,
            },
        },

        methods: {
            onChange(index, value) {
                const values = [...this.values];
                values[index][this.valueField] = value;
                this.$emit('change', values);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .listbox {
        border: 1px solid #c5d9e8;
        border-radius: 4px;
        transition: border 0.2s;

        margin-bottom: 0.75em;
        padding: 8px 12px;

        min-height: 40px;
        max-height: 200px;

        overflow-y: auto;

        &:hover {
            border-color: #79a1eb;
        }
    }
</style>
