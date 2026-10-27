<template>
    <div class="dropdown">
        <at-dropdown :placement="position" :trigger="trigger" @on-dropdown-command="onExport">
            <at-button type="text">
                <span class="icon icon-save" />
            </at-button>

            <at-dropdown-menu slot="menu">
                <at-dropdown-item v-for="(type, key) in types" :key="key" :name="key">{{
                    key.toUpperCase()
                }}</at-dropdown-item>
            </at-dropdown-menu>
        </at-dropdown>
    </div>
</template>

<script>
    import AboutService from '@/services/resource/about.service';
    import { isHandledRequestError } from '@/utils/requestError';

    const aboutService = new AboutService();

    export default {
        name: 'ExportDropdown',
        props: {
            position: {
                type: String,
                default: 'bottom-left',
            },
            trigger: {
                type: String,
                default: 'click',
            },
        },
        data: () => ({
            types: [],
        }),
        async created() {
            try {
                this.types = await aboutService.getReportTypes();
            } catch (error) {
                if (!isHandledRequestError(error)) {
                    throw error;
                }
            }
        },
        methods: {
            onExport(format) {
                this.$emit('export', this.types[format]);
            },
            onClose() {
                this.$emit('close');
            },
        },
    };
</script>

<style lang="scss" scoped>
    .dropdown {
        display: block;
        width: 40px;
        height: 40px;

        display: flex;
        align-items: center;
        justify-content: center;

        &::v-deep .at-btn__text {
            color: #2e2ef9;
            font-size: 25px;
        }
    }

    .at-dropdown-menu {
        right: 5px;
        border-radius: 10px;
    }
</style>
