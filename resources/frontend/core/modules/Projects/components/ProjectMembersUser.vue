<template>
    <li class="user-item flex flex-middle" :class="{ 'user-item--selected': selected }" @click="$emit('click', $event)">
        <user-avatar class="user-item__avatar" :user="user" />
        <div>{{ user.full_name }}</div>
        <role-select
            v-if="!addable"
            v-model="roleId"
            class="user-item__role-select"
            :exclude-roles="['admin']"
            @click.stop
        >
            <template v-slot:role_manager_description>
                {{ $t('project-roles-description.manager') }}
            </template>
            <template v-slot:role_auditor_description>
                {{ $t('project-roles-description.auditor') }}
            </template>
            <template v-slot:role_user_description>
                {{ $t('project-roles-description.user') }}
            </template>
        </role-select>
    </li>
</template>

<script>
    import UserAvatar from '@/components/UserAvatar.vue';
    import RoleSelect from '@/components/RoleSelect.vue';

    export default {
        name: 'ProjectMembersUser',
        components: {
            UserAvatar,
            RoleSelect,
        },
        props: {
            user: {
                required: true,
                type: Object,
            },
            selected: {
                type: Boolean,
                default: false,
            },
            addable: {
                type: Boolean,
                default: false,
            },
        },
        computed: {
            roleId: {
                get() {
                    const roleId = this.user?.pivot?.role_id;

                    if (roleId === undefined) {
                        const defaultRoleId = 2;
                        this.$emit('role-change', defaultRoleId);
                        return defaultRoleId;
                    }

                    return roleId;
                },
                set(roleId) {
                    this.$emit('role-change', roleId);
                },
            },
        },
    };
</script>

<style lang="scss" scoped>
    .user-item {
        min-height: 57px;
        padding: 0.5rem 1rem;
        border-bottom: 1px solid $border-color-base;

        &:hover {
            background: $table-tr-bg-color-hover;
            cursor: pointer;
        }

        &--selected {
            background: darken($table-tr-bg-color-hover, 5%);

            &:hover {
                background: darken($table-tr-bg-color-hover, 4%);
                cursor: pointer;
            }
        }

        &__avatar {
            margin-right: $spacing-05;
        }

        &__role-select {
            max-width: 180px;
            margin-left: auto;
        }
    }
</style>
