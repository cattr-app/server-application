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
        <div v-for="(user, key) in users" :key="key" class="row team_sidebar__user_wrapper">
            <div class="col-12 row team_sidebar__user_row">
                <UserAvatar :user="user" />
                <div class="team_sidebar__user_info col-24">
                    <div class="team_sidebar__user_name">{{ user.full_name }}</div>
                    <div class="team_sidebar__user_task">
                        <router-link
                            v-if="user.last_interval"
                            :to="`/tasks/view/${user.last_interval.task_id}`"
                            :title="user.last_interval.task_name"
                            target="_blank"
                        >
                            {{ user.last_interval.project_name }}
                        </router-link>
                    </div>
                </div>
            </div>
            <div class="col-12 flex-end team_sidebar__user_worked">
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
                margin-bottom: 15px;
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
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            &_row {
                height: 65px;
                flex-wrap: nowrap;
                align-items: center;
            }

            &_worked {
                color: #59566e;
                font-weight: 600;
                display: flex;
                align-items: center;
                white-space: nowrap;
            }

            &_task {
                font-size: 9pt;
                display: block;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            &_info {
                margin-top: 0;
            }
        }
        @media (max-width: 780px) {
            .team_sidebar {
                &__heading {
                    display: grid;
                    grid-template-columns: 100%;
                    grid-template-rows: repeat(2, calc(39px / 2));
                    font-size: 0.8rem;
                    & > div {
                        max-width: 100%;
                        justify-self: start;
                    }
                }
                &__user_wrapper {
                    height: 65px;
                    display: grid;
                    grid-template-rows: 3fr 1fr;
                    grid-template-columns: 100%;
                }
                &__user_task {
                    display: none;
                }
                &__user_worked {
                    max-width: 100%;
                    align-self: flex-end;
                    font-size: 0.6rem;
                }
                &__user_row {
                    max-width: 80%;
                    height: auto;
                    align-self: end;
                }
            }
            .hidden {
                display: none;
            }
        }
    }
</style>
