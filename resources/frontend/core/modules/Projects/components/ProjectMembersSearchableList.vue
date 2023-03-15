<template>
    <div>
        <at-input v-model="search" class="search-input" :placeholder="$t('control.search')">
            <template slot="prepend">
                <i class="icon icon-search" />
            </template>
        </at-input>
        <ul class="user-list">
            <preloader v-if="loading" class="user-list__preloader" />
            <template v-else>
                <project-members-user
                    v-for="(user, index) in filteredUsers"
                    :key="index"
                    class="list-item"
                    :user="user"
                    :selected="selectedUsers.includes(user)"
                    :addable="addable"
                    @role-change="onRoleChange($event, user.id)"
                    @click="onClick(user)"
                />
            </template>
        </ul>
    </div>
</template>

<script>
    import ProjectMembersUser from './ProjectMembersUser.vue';
    import Preloader from '@/components/Preloader.vue';

    export default {
        name: 'ProjectMembersSearchableList',
        components: {
            ProjectMembersUser,
            Preloader,
        },
        props: {
            value: {
                type: Array,
                default: () => [],
            },
            selectedUsers: {
                type: Array,
                default: () => [],
            },
            addable: {
                type: Boolean,
                default: false,
            },
            loading: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            return {
                search: '',
            };
        },
        computed: {
            filteredUsers() {
                if (this.search.length > 0) {
                    return this.filterList(this.search, this.value, 'full_name');
                }
                return this.value;
            },
        },
        methods: {
            onRoleChange(roleId, userId) {
                const users = Array.from(this.value);
                const userIndex = users.findIndex(user => user.id === userId);

                if (userIndex === -1) {
                    return;
                }

                users[userIndex]['pivot'] = {
                    role_id: roleId,
                };

                this.$emit('input', users);
            },
            onClick(user) {
                const users = this.selectedUsers;
                const userIndex = users.findIndex(u => u.id === user.id);

                if (userIndex > -1) {
                    users.splice(userIndex, 1);
                } else {
                    users.push(user);
                }

                this.$emit('on-select', users);
            },
            filterList(q, list, field) {
                const words = q
                    .split(' ')
                    .map(s => s.trim())
                    .filter(s => s.length !== 0);
                const hasTrailingSpace = q.endsWith(' ');
                const escapeRegExp = s => s.replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
                const regexString = words
                    .map((word, i) => {
                        if (i + 1 === words.length && !hasTrailingSpace) return `(?=.*\\b${escapeRegExp(word)})`;
                        return `(?=.*\\b${escapeRegExp(word)}\\b)`;
                    })
                    .join('');
                const searchRegex = new RegExp(`${regexString}.+`, 'gi');
                return list.filter(item => searchRegex.test(item[field]));
            },
        },
    };
</script>

<style lang="scss" scoped>
    .search-input {
        margin-bottom: $layout-01;
    }

    .user-list {
        border: 1px solid $border-color-base;
        height: 400px;
        overflow-y: auto;
        border-radius: 5px;
        list-style: none;
        position: relative;

        &__preloader {
            bottom: 0;
            top: 0;
            right: 0;
            left: 0;
        }
    }
</style>
