<template>
    <div class="container-fluid">
        <div class="row flex-around">
            <div class="col-24 col-sm-22 col-lg-20 at-container">
                <div class="crud crud__content">
                    <preloader v-if="fetching" is-transparent></preloader>
                    <div class="page-controls">
                        <h1 class="page-title crud__title">
                            {{ $t('tasks.relations.for') }}: {{ this.task.task_name }}
                        </h1>
                        <div class="control-items">
                            <div class="control-item">
                                <at-button size="large" @click="$router.go(-1)">{{ $t('control.back') }}</at-button>
                            </div>
                        </div>
                    </div>
                    <relations-selector
                        v-if="task.project_id"
                        :parents="parents"
                        :children="children"
                        :project-id="task.project_id"
                        :show-controls="true"
                        @unlink="handleUnlink"
                        @createRelation="handleCreate"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import TasksService from '@/services/resource/task.service';
    import GanttService from '@/services/resource/gantt.service';
    import RelationsSelector from '../components/RelationsSelector.vue';
    import Preloader from '@/components/Preloader.vue';

    const tasksService = new TasksService();
    const ganttService = new GanttService();
    export default {
        name: 'TaskRelations',
        components: {
            Preloader,
            RelationsSelector,
        },
        data() {
            return {
                task: {},
                parents: [],
                children: [],

                saving: false,
                fetching: false,
            };
        },
        async mounted() {
            try {
                this.fetching = true;

                const task = await tasksService.getItem(this.$route.params.id, {
                    with: ['status', 'parents', 'children'],
                });
                this.task = task.data.data;
                this.children = this.task.children;
                this.parents = this.task.parents;

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
            handleCreate(relation) {
                ganttService.createRelation(this.task.id, relation).then(res => {
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.record.save.success.title'),
                        message: this.$t('notification.record.save.success.message'),
                    });
                    const task = res.data.data;
                    if (relation.type === 'follows') {
                        this.parents.push(task);
                    } else {
                        this.children.push(task);
                    }
                });
                // .catch(e => {
                //     this.$Notify({
                //         type: 'error',
                //         title: this.$t('notification.save.error.title'),
                //         message: e.response.data.message ?? this.$t('notification.save.error.message'),
                //     });
                // });
            },
            handleUnlink(relatedTask) {
                const isParent = relatedTask.pivot.child_id === this.task.id;

                ganttService.removeRelation(relatedTask.pivot).then(res => {
                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.record.delete.success.title'),
                        message: this.$t('notification.record.delete.success.message'),
                    });
                    if (isParent) {
                        this.parents.splice(relatedTask.index, 1);
                    } else {
                        this.children.splice(relatedTask.index, 1);
                    }
                });
            },
        },
        computed: {},
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
    .page-controls {
        margin-bottom: 1.5em;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
</style>
