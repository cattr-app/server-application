<template>
    <div class="container">
        <div class="at-container">
            <div class="crud crud__content">
                <div class="page-controls">
                    <h1 class="page-title crud__title">{{ $t('projects.project_members') }}</h1>
                    <div class="control-items">
                        <div class="control-item">
                            <at-button size="large" @click="$router.go(-1)">{{ $t('control.back') }}</at-button>
                        </div>
                    </div>
                </div>
                <div class="project-members-form">
                    <div class="row flex-middle flex-between">
                        <div class="col-md-11">
                            <project-members-searchable-list
                                v-model="addableUsers"
                                addable
                                :loading="fetching"
                                :selected-users="selectedUsersToAdd"
                                @on-select="onUserSelect"
                            />
                        </div>
                        <div class="col-md-1">
                            <at-button
                                type="info"
                                hollow
                                size="small"
                                class="project-members-form__action-btn"
                                :disabled="!selectedUsersToAdd.length"
                                @click="addUsers"
                            >
                                <i class="icon icon-chevrons-right"></i>
                            </at-button>
                            <at-button
                                type="info"
                                hollow
                                size="small"
                                class="project-members-form__action-btn"
                                :disabled="!selectedUsersToRemove.length"
                                @click="removeUsers"
                            >
                                <i class="icon icon-chevrons-left"></i>
                            </at-button>
                        </div>
                        <div class="col-md-11">
                            <project-members-searchable-list
                                v-model="projectUsers"
                                :loading="fetching"
                                :selected-users="selectedUsersToRemove"
                                @on-select="onProjectUserSelect"
                            />
                        </div>
                    </div>
                    <at-button size="large" type="primary" :loading="saving" :disabled="saving" @click="save()">{{
                        $t('control.save')
                    }}</at-button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import ProjectService from '@/services/resource/project.service';
    import UsersService from '@/services/resource/user.service';
    import ProjectMembersSearchableList from '../components/ProjectMembersSearchableList.vue';

    export default {
        name: 'ProjectMembers',
        components: {
            ProjectMembersSearchableList,
        },
        data() {
            return {
                project: {},
                projectUsers: [],
                users: [],
                projectService: new ProjectService(),
                usersService: new UsersService(),

                selectedUsersToAdd: [],
                selectedUsersToRemove: [],

                saving: false,
                fetching: false,
            };
        },
        async mounted() {
            try {
                this.fetching = true;

                const project = await this.projectService.getItem(this.$route.params[this.projectService.getIdParam()]);
                this.project = project.data.data;
                const projectUsers = await this.projectService.getMembers(
                    this.$route.params[this.projectService.getIdParam()],
                );
                this.projectUsers = projectUsers.data.data.users;

                const params = { global_scope: true };
                this.users = await this.usersService.getAll({ params, headers: { 'X-Paginate': 'false' } });
            } catch (e) {
                //
            } finally {
                this.fetching = false;
            }
        },
        methods: {
            async save() {
                let userRoles = [];

                this.projectUsers.forEach(user =>
                    userRoles.push({
                        user_id: user.id,
                        role_id: user.pivot.role_id,
                    }),
                );

                const data = {
                    project_id: this.project.id,
                    user_roles: userRoles,
                };

                try {
                    this.saving = true;
                    await this.projectService.bulkEditMembers(data);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.save.success.title'),
                        message: this.$t('notification.save.success.message'),
                    });
                } catch (e) {
                    //
                } finally {
                    this.saving = false;
                }
            },
            onUserSelect(selectedUsers) {
                this.selectedUsersToAdd = selectedUsers;
            },
            onProjectUserSelect(selectedUsers) {
                this.selectedUsersToRemove = selectedUsers;
            },
            addUsers() {
                const users = this.users.filter(user => {
                    for (const selectedUser of this.selectedUsersToAdd) {
                        if (selectedUser.id === user.id) {
                            this.selectedUsersToAdd.splice(
                                this.selectedUsersToAdd.findIndex(user => user.id === selectedUser.id),
                                1,
                            );
                            return true;
                        }
                    }
                    return false;
                });
                this.projectUsers = [...this.projectUsers, ...users];
            },
            removeUsers() {
                if (this.selectedUsersToRemove.length) {
                    this.projectUsers = this.projectUsers.filter(user => {
                        for (const selectedUser of this.selectedUsersToRemove) {
                            if (selectedUser.id === user.id) {
                                this.selectedUsersToRemove.splice(
                                    this.selectedUsersToRemove.findIndex(user => user.id === selectedUser.id),
                                    1,
                                );
                                return false;
                            }
                        }
                        return true;
                    });
                }
            },
        },
        computed: {
            addableUsers() {
                const users = Array.from(this.users);

                if (this.projectUsers.length) {
                    const addedUsersIds = this.projectUsers.map(u => u[this.usersService.getIdParam()]);
                    addedUsersIds.forEach(id => {
                        users.splice(
                            users.findIndex(user => {
                                return user[this.usersService.getIdParam()] === id;
                            }),
                            1,
                        );
                    });
                }
                return users;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .project-members-form {
        .row {
            margin-bottom: $layout-01;
        }

        &__action-btn {
            margin-bottom: $layout-01;
        }
    }
</style>
