<template>
    <div>
        <validation-provider v-slot="{ errors }" rules="required|email" mode="passive" name="E-mail">
            <div class="input-group">
                <small>E-Mail</small>
                <at-input
                    v-model="user.email"
                    name="login"
                    :status="errors.length > 0 ? 'error' : ''"
                    placeholder="E-Mail"
                    icon="mail"
                    type="text"
                    required
                    @keydown.native.enter.prevent="submit"
                ></at-input>
                <small>{{ errors[0] }}</small>
            </div>
            <!-- /.input-group -->
        </validation-provider>
        <validation-provider v-slot="{ errors }" rules="required" mode="passive" :name="$t('field.password')">
            <div class="input-group">
                <small>{{ $t('field.password') }}</small>
                <at-input
                    v-model="user.password"
                    name="password"
                    :status="errors.length > 0 ? 'error' : ''"
                    :placeholder="$t('field.password')"
                    type="password"
                    icon="lock"
                    required
                    @keydown.native.enter.prevent="submit"
                ></at-input>
                <small>{{ errors[0] }}</small>
            </div>
            <!-- /.input-group -->
        </validation-provider>
    </div>
</template>

<script>
    import { ValidationProvider } from 'vee-validate';

    export default {
        name: 'AuthInput',

        components: {
            ValidationProvider,
        },

        data() {
            return {
                user: {
                    email: null,
                    password: null,
                },
            };
        },

        methods: {
            submit() {
                this.$emit('submit');
            },
        },

        watch: {
            'user.email'(value) {
                // Trim space
                this.user.email = value.replace(/\s/, '');
                this.$emit('change', this.user);
            },
            'user.password'() {
                this.$emit('change', this.user);
            },
        },
    };
</script>
