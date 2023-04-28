<template>
    <at-select
        v-if="Object.keys(roles).length > 0"
        ref="select"
        class="role-select"
        :value="value"
        @on-change="inputHandler"
    >
        <at-option v-for="(role, name) in roles" :key="role" :value="role" :label="$t(`field.roles.${name}.name`)">
            <div>
                <slot :name="`role_${name}_name`">
                    {{ $t(`field.roles.${name}.name`) }}
                </slot>
            </div>
            <div class="role-select__description">
                <slot :name="`role_${name}_description`">
                    {{ $t(`field.roles.${name}.description`) }}
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
            excludeRoles: {
                type: Array,
                default: () => [],
            },
        },
        computed: {
            roles() {
                return Object.keys(this.$store.getters['roles/roles'])
                    .filter(key => !this.excludeRoles.includes(key))
                    .reduce((acc, el) => Object.assign(acc, { [el]: this.$store.getters['roles/roles'][el] }), {});
            },
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
