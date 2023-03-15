<template>
    <div class="root">
        <div class="status">
            <at-alert :message="message" :type="status" class="status__alert" show-icon />
            <at-button class="status__button" type="info" @click="checkConnection">
                {{ $t('setup.buttons.update') }}
            </at-button>
        </div>
        <p>{{ $t('setup.header.backend_ping.server_url', { serverUrl }) }}</p>
        <at-popover v-model="show" placement="bottom" @toggle="show = false">
            <p v-t="'setup.header.backend_ping.wrong_url'" class="wrong_server" />
            <template #content>
                <p v-t="'setup.header.backend_ping.building_when'" />
                <i18n path="setup.header.backend_ping.read_more" tag="p">
                    <a href="https://docs.cattr.app/#/en/advanced/?id=configuration-examples" target="_blank">
                        {{ $t('setup.header.backend_ping.documentation') }}
                    </a>
                </i18n>
            </template>
        </at-popover>
    </div>
</template>

<script>
    import ApiService from '@/services/api';

    const apiService = new ApiService();

    export default {
        name: 'BackendPing',
        data() {
            return {
                status: 'process',
                serverUrl: apiService.serverUrl,
                show: false,
            };
        },
        mounted() {
            this.checkConnection();
        },
        computed: {
            message() {
                return (
                    this.$t(`setup.header.backend_ping.status`) +
                    ': ' +
                    this.$t(`setup.header.backend_ping.${this.status}`)
                );
            },
        },
        methods: {
            async checkConnection() {
                this.status = 'process';

                try {
                    const data = await apiService.status();

                    if (!('cattr' in data) || !data.cattr) {
                        throw 'Not cattr';
                    }

                    this.status = 'success';
                } catch (e) {
                    this.status = 'error';
                }
            },
        },
        watch: {
            status(status) {
                this.$emit('setStatus', status);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .root {
        text-align: center;

        .wrong_server {
            color: $brand-color;
            cursor: pointer;
            font-size: 11pt;
            text-decoration: underline;
            text-decoration-style: double;
        }

        .status {
            display: flex;

            &__button {
                margin-left: 10px;
            }

            &__alert {
                min-width: 300px;
            }
        }
    }
</style>
