<template>
    <div class="permission">
        <div class="permission__header">
            <at-checkbox v-model="permission" label="Shenzhen" @on-change="handlePermission" />
            <u>Получать, проверять обновления для модулей и их совместимость</u>
        </div>
        <div v-if="permission" class="permission__registor">
            <span class="title">Регистрация в коллекторе</span>
            <div class="permission__status">
                <at-alert
                    v-if="showStatusRegistration"
                    class="permission__alert"
                    :type="typeStatus"
                    :message="message"
                    show-icon
                />
                <at-checkbox v-model="permission" label="Shenzhen" @on-change="handlePermission" />
                <span>Разрешить отправку анонимной статистики</span>
                <at-button type="info" @click="onRegistration">{{
                    $t('setup.header.permission.registration')
                }}</at-button>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'permission',
        data() {
            return {
                permission: true,
                message: this.$t('setup.header.permission.registration_process'),
                typeStatus: 'info',
                status: 'process',
                showStatusRegistration: false,
            };
        },
        mounted() {
            this.$emit('setState', { permission: { status: 'process' } });
        },
        methods: {
            onRegistration() {
                this.showStatusRegistration = true;
            },
            handlePermission(val) {
                if (!val) {
                    this.permission = val;
                    this.status = 'finish';
                }
                this.$emit('setState', { permission: { status: val ? 'process' : this.status } });
            },
        },
    };
</script>

<style lang="scss" scoped>
    .permission {
        &__header {
            display: flex;
            margin-bottom: 16px;
        }
        &__registor {
            text-align: left;
        }
        &__status {
            display: flex;
            justify-content: flex-end;
        }
        &__alert {
            margin-right: 10px;
        }
    }
</style>
