<template>
    <div>
        <at-collapse simple class="list">
            <at-collapse-item class="list__item">
                <div class="task-title" slot="title">
                    {{ task.task_name }}
                    <at-button
                        v-if="task.task_name"
                        class="icon"
                        icon="icon-external-link"
                        @click="redirectToTask"
                    ></at-button>
                </div>
                <div>
                    <div class="data-entries">
                        <h3>{{ $t('universal-report.task_information') }}</h3>
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
</template>

<script>
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        methods: {
            redirectToTask() {
                this.$router.push({
                    name: 'Tasks.crud.tasks.view',
                    params: { id: this.id },
                });
            },
        },
        props: {
            id: {
                type: [Number, String],
                required: true,
            },
            task: {
                type: Object,
                required: true,
            },
        },
        components: {
            Skeleton,
        },
    };
</script>
<style lang="scss" scoped>
    .task-title {
        color: black;
        font-weight: bold;
    }
</style>
