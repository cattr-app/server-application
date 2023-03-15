<template>
    <div>
        <div v-for="status in statuses" :key="status.id" class="status">
            <h3 class="status-title">{{ status.name }}</h3>

            <div>
                <at-checkbox
                    class="status-enable"
                    :checked="enabled(status.id)"
                    @on-change="onEnabledChange(status.id, $event)"
                >
                    {{ $t('projects.enable_status') }}
                </at-checkbox>
            </div>

            <div v-if="enabled(status.id)">
                <at-checkbox
                    class="status-color-override"
                    :checked="overrideColor(status.id)"
                    @on-change="onOverrideColorChange(status.id, $event)"
                >
                    {{ $t('projects.override_color') }}
                </at-checkbox>

                <ColorInput
                    v-if="overrideColor(status.id)"
                    class="status-color"
                    :value="color(status.id)"
                    @change="onColorChange(status.id, $event)"
                />
            </div>
        </div>
    </div>
</template>

<script>
    import ColorInput from '@/components/ColorInput';
    import StatusService from '@/services/resource/status.service';

    export default {
        components: {
            ColorInput,
        },
        props: {
            value: {
                required: true,
            },
        },
        data() {
            return {
                statusService: new StatusService(),
                statuses: [],
            };
        },
        async created() {
            this.statuses = await this.statusService.getAll();
        },
        methods: {
            getStatusIndex(id) {
                return this.value.findIndex(status => +status.id === +id);
            },
            getStatus(id) {
                return this.value.find(status => +status.id === +id);
            },
            enabled(id) {
                return this.getStatus(id) !== undefined;
            },
            overrideColor(id) {
                const status = this.getStatus(id);

                return status !== undefined && status.color !== null;
            },
            color(id) {
                const status = this.getStatus(id);

                return status !== undefined && status.color !== null ? status.color : 'transparent';
            },
            onEnabledChange(id, value) {
                let newValue = value
                    ? Array.from(new Set([...this.value, { id: +id, color: null }]))
                    : this.value.filter(status => +status.id !== +id);

                this.$emit('change', newValue);
            },
            onOverrideColorChange(id, value) {
                const index = this.getStatusIndex(id);
                let newValue = [...this.value];
                if (value) {
                    newValue.splice(index, 1, { id: +id, color: '#ffffff' });
                } else {
                    newValue.splice(index, 1, { id: +id, color: null });
                }

                this.$emit('change', newValue);
            },
            onColorChange(id, value) {
                const index = this.getStatusIndex(id);
                let newValue = [...this.value];
                newValue.splice(index, 1, { id: +id, color: value });

                this.$emit('change', newValue);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .status {
        &:not(:last-child) {
            margin-bottom: 24px;
        }

        &-title {
            font-size: 16px;
        }

        &-color-override {
            margin-bottom: 16px;
        }

        &-color {
            width: 170px;
            height: 40px;
            overflow: hidden;
        }
    }
</style>
