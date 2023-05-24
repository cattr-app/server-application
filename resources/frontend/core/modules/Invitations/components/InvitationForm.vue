<template>
    <div class="invite-form">
        <validation-observer ref="form">
            <div v-for="(user, index) in users" :key="index" class="row invite-form__group">
                <validation-provider v-slot="{ errors }" :vid="`users.${index}.email`" class="col-14">
                    <at-input
                        v-model="users[index]['email']"
                        :placeholder="$t('field.email')"
                        :status="errors.length > 0 ? 'error' : ''"
                    >
                        <template slot="prepend">{{ $t('field.email') }}</template>
                    </at-input>
                    <small>{{ errors[0] }}</small>
                </validation-provider>
                <validation-provider :vid="`users.${index}.role_id`" class="col-6">
                    <role-select v-model="users[index]['role_id']"></role-select>
                </validation-provider>
                <at-button v-if="index > 0" class="col-2 invite-form__remove" @click="removeUser(index)"
                    ><i class="icon icon-x"></i
                ></at-button>
            </div>
        </validation-observer>
        <at-button type="default" size="small" class="col-4" @click="handleAdd">{{ $t('control.add') }}</at-button>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import RoleSelect from '@/components/RoleSelect';

    export default {
        name: 'InviteInput',
        components: {
            ValidationObserver,
            ValidationProvider,
            RoleSelect,
        },
        props: {
            value: {
                type: [Array, Object],
            },
        },
        data() {
            return {
                users: [
                    {
                        email: null,
                        role_id: 2,
                    },
                ],
            };
        },
        mounted() {
            this.$emit('input', this.users);
        },
        methods: {
            handleAdd() {
                this.users.push({ email: null, role_id: 2 });
            },
            removeUser(index) {
                this.users.splice(index, 1);
            },
        },
        watch: {
            users(value) {
                this.$emit('input', value);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .invite-form {
        &__group {
            margin-bottom: 1rem;
        }

        &__remove {
            max-height: 40px;
        }
    }
</style>
