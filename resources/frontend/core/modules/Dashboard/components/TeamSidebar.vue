<template>
    <div class="team_sidebar">
        <div class="row team_sidebar__heading">
            <div class="col-12">
                <span
                    :class="{ 'team_sidebar__heading-active': this.sort === 'user' }"
                    class="team_sidebar__heading-toggle"
                    @click="selectColumn('user')"
                    >{{ $t('dashboard.user') }}
                    <template v-if="this.sort === 'user'">
                        <i v-if="this.sortDir === 'asc'" class="icon icon-chevron-down"></i>
                        <i v-else class="icon icon-chevron-up"></i>
                    </template>
                </span>
            </div>
            <div class="col-12 flex-end">
                <span
                    :class="{ 'team_sidebar__heading-active': this.sort === 'worked' }"
                    class="team_sidebar__heading-toggle"
                    @click="selectColumn('worked')"
                    >{{ $t('dashboard.worked') }}
                    <template v-if="this.sort === 'worked'">
                        <i v-if="this.sortDir === 'desc'" class="icon icon-chevron-down"></i>
                        <i v-else class="icon icon-chevron-up"></i>
                    </template>
                </span>
            </div>
        </div>
        <div v-for="(user, key) in users" :key="key" class="row">
            <div class="col-16 row team_sidebar__user_row">
                <div class="col-4">
                    <UserAvatar :user="user" />
                </div>
                <div class="team_sidebar__user_info col-offset-1">
                    <div class="team_sidebar__user_name">{{ user.full_name }}</div>
                    <div class="team_sidebar__user_task">
                        <router-link
                            v-if="user.last_interval"
                            :to="`/tasks/view/${user.last_interval.task_id}`"
                            :title="user.last_interval.task_name"
                            target="_blank"
                        >
                            {{ user.last_interval.project_name | truncate }}
                        </router-link>
                        <a v-else>&nbsp;</a>
                    </div>
                </div>
            </div>
            <div class="col-8 flex-end team_sidebar__user_worked">
                {{ formatDurationString(user.worked) }}
            </div>
        </div>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import { mapGetters } from 'vuex';
    import UserAvatar from '@/components/UserAvatar';

    export default {
        name: 'TeamSidebar',
        components: { UserAvatar },
        props: {
            sort: {
                type: String,
                required: true,
            },
            sortDir: {
                type: String,
                required: true,
            },
            users: {
                type: Array,
                required: true,
            },
        },
        computed: {
            ...mapGetters('dashboard', ['intervals']),
        },
        filters: {
            truncate(value) {
                return value.length >= 25 ? value.substring(0, 25) + '...' : value;
            },
        },
        methods: {
            formatDurationString,
            selectColumn(column) {
                this.$emit('sort', column);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .team_sidebar {
        &__heading {
            font-weight: 600;
            color: #b1b1be;
            padding-right: 9px;

            &-active {
                color: #59566e;
                padding-right: 14px;
            }
            &-toggle {
                cursor: pointer;
                display: inline-block;
                margin-bottom: 20px;
                position: relative;
                .icon {
                    position: absolute;
                    top: 50%;
                    right: -3px;
                    transform: translateY(-46%);
                }
            }
        }

        &__user {
            &_name {
                font-size: 10pt;
                font-weight: 500;
                color: #151941;
            }

            &_row {
                margin: 16px 0;
            }

            &_worked {
                color: #59566e;
                font-weight: 600;
                margin-top: 15px;
                padding-right: 15px;
            }

            &_task {
                font-size: 9pt;
            }

            &_info {
                margin-top: -5px;
            }
        }
    }
</style>
