<template>
    <at-select v-if="roles.length > 0" ref="select" class="role-select" :value="value" @on-change="inputHandler">
        <at-option v-for="(role, index) in roles" :key="index" :value="role.id" :label="$t('users.role.' + role.name)">
            <div>{{ $t('users.role.' + role.name) }}</div>
            <div class="role-select__description">
                <slot :name="['role-description', role.name]">
                    {{ $t('users.roles-description.' + role.name) }}
                </slot>
            </div>
        </at-option>
    </at-select>
</template>

<script>
    import { mapGetters, mapActions } from 'vuex';
    import { ucfirst } from '@/utils/string';

    export default {
        props: {
            value: Number,
        },
        computed: {
            ...mapGetters('roles', ['roles']),
        },
        methods: {
            ucfirst,
            ...mapActions({
                getRoles: 'roles/loadRoles',
            }),
            inputHandler(value) {
                this.$emit('input', value);
                this.$emit('updateProps', value);
            },
        },
        async created() {
            await this.getRoles();

            if (this.$refs.select && Object.prototype.hasOwnProperty.call(this.$refs.select, '$children')) {
                this.$refs.select.$children.forEach(option => {
                    option.hidden = false;
                });
            }
        },
    };
</script>

<style lang="scss" scoped>
    .role-select {
        &__description {
            white-space: normal;
            opacity: 0.6;
            font-size: 0.7rem;
        }
    }
</style>
