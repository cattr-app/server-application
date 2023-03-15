<template>
    <div>
        <div class="total-time">
            <h5>{{ $t('dashboard.total_time') }}:</h5>
            <h5>
                <Skeleton :loading="isDataLoading" width="50px">{{ totalTime }} </Skeleton>
            </h5>
        </div>

        <div v-for="project in userProjects" :key="project.id" class="project">
            <div class="project__header">
                <Skeleton :loading="isDataLoading" width="100%" height="15px">
                    <div class="project__title">
                        <span class="project__name" :title="project.name">
                            <router-link class="task__title-link" :to="`/projects/view/${project.id}`">
                                {{ project.name }}
                            </router-link>
                        </span>
                        <span class="project__duration">
                            {{ formatDurationString(project.durationAtSelectedPeriod) }}
                        </span>
                    </div>
                    <!-- /.project-title -->
                </Skeleton>
            </div>
            <!-- /.project-header -->

            <ul class="task-list">
                <li
                    v-for="task in getVisibleTasks(project.id)"
                    :key="task.id"
                    class="task"
                    :class="{ 'task-active': activeTask === task.id }"
                >
                    <Skeleton :loading="isDataLoading" width="100%" height="15px">
                        <h3 class="task__title" :title="task.name">
                            <router-link class="task__title-link" :to="`/tasks/view/${task.id}`">
                                {{ task.name }}
                            </router-link>
                        </h3>

                        <div class="task__progress">
                            <at-progress
                                class="task__progressbar"
                                status="success"
                                :stroke-width="5"
                                :percent="getPercentForTaskInProject(task, project)"
                            ></at-progress>

                            <span class="task__duration">
                                {{ formatDurationString(task.durationAtSelectedPeriod) }}
                            </span>
                        </div>
                    </Skeleton>
                </li>
            </ul>

            <template v-if="getAllTasks(project.id).length > 3">
                <at-button
                    v-if="!isExpanded(project.id)"
                    class="project__expand"
                    type="text"
                    @click.prevent="expand(project.id)"
                >
                    {{ $t('projects.show-more') }}
                </at-button>

                <at-button v-else class="project__shrink" type="text" @click.prevent="shrink(project.id)">
                    {{ $t('projects.show-less') }}
                </at-button>
            </template>
        </div>
    </div>
</template>

<script>
    import { mapGetters } from 'vuex';
    import { formatDurationString } from '@/utils/time';
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        name: 'TimelineSidebar',
        components: {
            Skeleton,
        },
        props: {
            activeTask: {
                type: Number,
            },
            isDataLoading: {
                type: Boolean,
                default: false,
            },
            startDate: {
                type: String,
            },
            endDate: {
                type: String,
            },
        },
        data() {
            return {
                expandedProjects: [],
            };
        },
        computed: {
            ...mapGetters('dashboard', ['timePerProject']),
            ...mapGetters('user', ['user']),
            userProjects() {
                if (!this.user || !this.user.id) {
                    return [];
                }

                if (!this.timePerProject[this.user.id]) {
                    return [];
                }

                return Object.values(this.timePerProject[this.user.id]);
            },
            totalTime() {
                const sum = (totalTime, project) => (totalTime += project.durationAtSelectedPeriod);
                return formatDurationString(this.userProjects.reduce(sum, 0));
            },
        },
        methods: {
            isExpanded(projectID) {
                return this.expandedProjects.indexOf(+projectID) !== -1;
            },
            expand(projectID) {
                this.expandedProjects.push(+projectID);
            },
            shrink(projectID) {
                this.expandedProjects = this.expandedProjects.filter(proj => +proj !== +projectID);
            },
            getAllTasks(projectID) {
                return Object.values(this.timePerProject[this.user.id][projectID].tasks);
            },
            getVisibleTasks(projectID) {
                const tasks = this.getAllTasks(projectID);
                return this.isExpanded(projectID) ? tasks : tasks.slice(0, 3);
            },
            getPercentForTaskInProject(task, project) {
                return (100 * task.durationAtSelectedPeriod) / project.durationAtSelectedPeriod;
            },
            formatDurationString,
        },
    };
</script>

<style lang="scss" scoped>
    .total-time {
        display: flex;
        justify-content: space-between;
        align-items: center;

        padding: 0 20px;
        margin-bottom: $spacing-05;
    }

    .project {
        &__header {
            padding: 0 20px;
            margin-bottom: 5px;
        }

        &__title {
            display: flex;
            flex-flow: row nowrap;
            justify-content: space-between;
            align-items: baseline;
            color: #151941;
            font-size: 20px;
            font-weight: bold;
            white-space: nowrap;
        }

        &__name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        &__duration {
            float: right;
            margin-left: 0.5em;
            font-size: 15px;
        }

        &__expand,
        &__shrink {
            display: block;
            color: #b1b1be;
            padding: 0;
            margin: 5px 0 0 20px;

            &::v-deep .at-btn__text {
                font-size: 14px;
            }
        }

        &:not(:last-child) {
            margin-bottom: 35px;
        }
    }

    .task-list {
        list-style: none;
    }

    .task {
        color: #b1b1be;
        padding: 5px 20px;

        &::v-deep {
            .at-progress-bar {
                padding-right: 0;
            }

            .at-progress-bar__wraper {
                background: #e0dfed;
            }

            .at-progress--success .at-progress-bar__inner {
                background: #2dc38d;
            }

            .at-progress__text {
                display: none;
            }
        }

        &__title {
            color: inherit;
            white-space: nowrap;
            overflow: hidden;
            font-size: 15px;
            font-weight: 600;
            text-overflow: ellipsis;
        }

        &__title-link {
            color: inherit;
        }

        &__active {
            background: #f4f4ff;
            color: #151941;
            border-left: 3px solid #2e2ef9;

            &::v-deep {
                .at-progress-bar__wraper {
                    background: #b1b1be;
                }
            }
        }

        &__progress {
            display: flex;
            flex-flow: row nowrap;
            justify-content: space-between;
            align-items: center;
        }

        &__progressbar {
            flex: 1;
        }

        &__duration {
            margin-left: 1em;
            color: #59566e;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
    }
</style>
