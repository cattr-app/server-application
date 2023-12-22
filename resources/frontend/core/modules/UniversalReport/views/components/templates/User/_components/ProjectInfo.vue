<template>
    <at-collapse simple class="list">
        <at-collapse-item class="list__item">
            <div slot="title" class="project-title">
                {{ project?.name }}
                <at-button v-if="project.name" class="icon" icon="icon-external-link" @click="redirectToProject" />
            </div>
            <div v-if="project">
                <div class="data-entries">
                    <h3>{{ $t('universal-report.project_information') }}</h3>
                    <div v-if="'name' in project" class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t('field.name') }}:</div>
                            <div class="col">
                                <Skeleton>
                                    <span>{{ project.name }}</span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div v-if="'created_at' in project" class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t('field.created_at') }}:</div>
                            <div class="col">
                                <Skeleton>
                                    <span>{{ project.created_at }}</span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div v-if="'important' in project" class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t('field.important') }}:</div>
                            <div class="col">
                                <Skeleton>
                                    <span>{{ project.important ? 'yes' : 'no' }}</span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                    <div v-if="'description' in project" class="data-entry">
                        <div class="row">
                            <div class="col-6 label">{{ $t('field.description') }}:</div>
                            <div class="col">
                                <Skeleton>
                                    <span>{{ project.description }}</span>
                                </Skeleton>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="Object.keys(project?.tasks ?? []).length">
                    <h3>{{ $t('field.tasks') }}</h3>
                    <div v-for="(task, id) in project.tasks" :key="id">
                        <TaskInfo :id="task.id" :task="task" />
                    </div>
                </div>
            </div>
        </at-collapse-item>
    </at-collapse>
</template>

<script>
    import { Skeleton } from 'vue-loading-skeleton';
    import TaskInfo from './TaskInfo';

    export default {
        methods: {
            redirectToProject() {
                this.$router.push({
                    name: 'Projects.crud.projects.view',
                    params: { id: this.id },
                });
            },
        },
        props: {
            id: {
                type: [Number, String],
                required: true,
            },
            project: {
                type: Object,
                required: true,
            },
        },
        components: {
            Skeleton,
            TaskInfo,
        },
    };
</script>
<style scoped lang="scss">
    .data-entries {
        margin-bottom: 16px;
    }
    .project-title {
        color: black;
        font-weight: bold;
    }
</style>
