<template>
    <div>
        <at-collapse simple class="list">
            <at-collapse-item v-for="(report, id) in formatedReports" :key="id" class="list__item">
                <div slot="title" class="item-header">
                    <div>
                        <div class="row flex-middle">
                            <div class="col-xs-10 col-md-10 col-lg-13">
                                <span class="h5">{{ report?.name ?? '' }}</span>
                            </div>
                            <div class="col-xs-offset-3 col-xs-7 col-md-3 col-lg-2">
                                <span class="h4">{{ formatDurationString(report?.total_spent_time ?? 0) }}</span>
                            </div>
                            <div class="col-xs-5 col-md-9 col-lg-8 d-xs-none">
                                <at-progress
                                    :percent="
                                        getUserPercentage(report?.total_spent_time ?? 0, report?.total_spent_time ?? 0)
                                    "
                                    class="time-percentage"
                                    status="success"
                                    :stroke-width="15"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="data-entries">
                        <h3>Информация о проекте:</h3>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.name') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.name }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.status') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.status }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.priority') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.priority }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <div class="row">
                                <div class="col-6 label">{{ $t('field.description') }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ report.description }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                        <div class="data-entry">
                            <h3>{{ $t('field.members') }}</h3>
                            <at-table
                                :columns="[
                                    { title: $t('field.full_name'), key: 'full_name' },
                                    { title: $t('field.email'), key: 'email' },
                                    { title: $t('field.total_spent'), key: 'total_spent_time_by_user' },
                                ]"
                                :data="report.users"
                            />
                        </div>
                        <div class="data-entry">
                            <h4>Отработано по дням:</h4>
                            <div v-for="(timeDay, day) in report.worked_time_day" :key="day" class="row">
                                <div class="col-6 label">{{ day }}:</div>
                                <div class="col">
                                    <Skeleton>
                                        <span>{{ formatDurationString(timeDay) }}</span>
                                    </Skeleton>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3>Задачи:</h3>
                        <div v-for="(task, id) in report.tasks" :key="id">
                            <at-collapse simple class="list">
                                <at-collapse-item class="list__item">
                                    <div slot="title" class="item-header">
                                        <router-link
                                            class="h5 link"
                                            :title="task.task_name"
                                            :to="{
                                                name: 'Tasks.crud.tasks.view',
                                                params: { id },
                                            }"
                                        >
                                            {{ task.task_name }}
                                        </router-link>
                                    </div>
                                    <div>
                                        <div class="data-entries">
                                            <h3>Информация о задаче:</h3>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.name') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task.task_name }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.priority') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task.priority }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.status') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task.status }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.due_date') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task?.due_date ?? 'Отсутствует' }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.estimate') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task?.estimate ?? 'Отсутствует' }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="data-entry">
                                                <div class="row">
                                                    <div class="col-6 label">{{ $t('field.description') }}:</div>
                                                    <div class="col">
                                                        <Skeleton>
                                                            <span>{{ task.description }}</span>
                                                        </Skeleton>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </at-collapse-item>
                            </at-collapse>
                        </div>
                    </div>
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import { formatDurationString } from '@/utils/time';
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        name: 'Project',
        props: {
            reports: {
                type: Object,
                required: true,
                default: () => {},
            },
        },
        components: {
            Skeleton,
        },
        data() {
            return {
                formatedReports: [],
            };
        },
        mounted() {
            this.formatingProjectsReport();
            this.$watch(
                'reports',
                val => {
                    this.formatingProjectsReport();
                },
                {
                    deep: true,
                },
            );
        },
        methods: {
            formatDurationString,
            getUserPercentage(minutes, totalTime) {
                return Math.floor((minutes * 100) / totalTime);
            },
            formatingProjectsReport() {
                for (let key in this.reports) {
                    let report = this.reports[key];
                    this.$set(this.formatedReports, this.formatedReports.length, {
                        id: key,
                        created_at: report.created_at,
                        description: report.description,
                        important: report.important,
                        name: report.name,
                        tasks: report.tasks,
                        users: Object.values(report.users),
                        worked_time_day: report.worked_time_day,
                    });
                }
            },
        },
    };
</script>
