<template>
    <div>
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
