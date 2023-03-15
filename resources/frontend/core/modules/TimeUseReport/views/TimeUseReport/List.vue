<template>
    <div v-if="Object.keys(reportsList).length">
        <at-collapse simple class="list">
            <at-collapse-item v-for="(userReport, index) in reportsList" :key="index" class="list__item">
                <div slot="title" class="item-header">
                    <div class="row flex-middle">
                        <div class="col-xs-4 col-md-2 col-lg-1">
                            <UserAvatar :user="userReport.user" :size="35" />
                        </div>
                        <div class="col-xs-10 col-md-10 col-lg-13">
                            <span class="h5">{{ userReport.user.full_name }}</span>
                        </div>
                        <div class="col-xs-offset-3 col-xs-7 col-md-3 col-lg-2">
                            <span class="h4">{{ formatDurationString(userReport.time) }}</span>
                        </div>
                        <div class="col-xs-5 col-md-9 col-lg-8 d-xs-none">
                            <at-progress
                                :percent="getUserPercentage(userReport.time, userReport.time)"
                                class="time-percentage"
                                status="success"
                                :stroke-width="15"
                            />
                        </div>
                    </div>
                </div>

                <div v-for="task in userReport.tasks" :key="task.task_id" class="row flex-middle at-collapse__header">
                    <div class="col-xs-7 col-md-6 col-lg-7 text-ellipsis">
                        <router-link
                            class="h5 link"
                            :title="task.project_name"
                            :to="{
                                name: projectViewRoute,
                                params: { id: task.project_id },
                            }"
                        >
                            {{ task.project_name }}
                        </router-link>
                    </div>
                    <div class="col-xs-7 col-md-6 col-lg-7 text-ellipsis">
                        <router-link
                            class="h5 link"
                            :title="task.name"
                            :to="{ name: taskViewRoute, params: { id: task.task_id } }"
                        >
                            {{ task.task_name }}
                        </router-link>
                    </div>
                    <div class="col-xs-offset-3 col-xs-7 col-md-3 col-lg-2">
                        <span class="h4">{{ formatDurationString(task.time) }}</span>
                    </div>
                    <div class="col-xs-5 col-md-9 col-lg-8 d-xs-none">
                        <at-progress
                            :percent="getUserPercentage(task.time, userReport.time)"
                            :stroke-width="15"
                            status="success"
                        />
                    </div>
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import { getModuleList } from '@/moduleLoader';
    import UserAvatar from '@/components/UserAvatar';

    export default {
        props: {
            reportsList: {},
        },
        components: {
            UserAvatar,
        },
        data() {
            let projectRoute = {},
                taskRoute = {};
            Object.values(getModuleList()).forEach(i => {
                const moduleName = i.moduleInstance.getModuleName();
                if (moduleName === 'AmazingCat_TasksModule') {
                    taskRoute = i.moduleInstance.getRoutes().find(route => route.name.includes('view'));
                }

                if (moduleName === 'AmazingCat_ProjectsModule') {
                    projectRoute = i.moduleInstance.getRoutes().find(route => route.name.includes('view'));
                }
            });

            return {
                data: [],
                total_time: null,
                taskViewRoute: taskRoute.name,
                projectViewRoute: projectRoute.name,
            };
        },
        methods: {
            formatDurationString,
            getUserPercentage(minutes, totalTime) {
                return Math.floor((minutes * 100) / totalTime);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .at-collapse {
        color: $gray-2;

        &__content .at-collapse__header {
            cursor: default;
        }
    }

    .link {
        color: $gray-2;

        &:hover {
            color: $gray-1;
        }
    }

    .text-ellipsis {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media screen and (max-width: 991px) {
        .d-xs-none {
            display: none;
        }
    }
</style>
