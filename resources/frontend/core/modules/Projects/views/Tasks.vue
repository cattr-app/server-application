<template>
    <div>
        <div class="crud crud__content">
            <div class="page-controls">
                <h1 class="page-title crud__title">{{ project.name }}</h1>

                <div class="control-items">
                    <div class="control-item">
                        <at-button
                            v-if="$can('create', 'task')"
                            type="primary"
                            size="large"
                            icon="icon-edit"
                            @click="$router.push({ name: 'Tasks.crud.tasks.new' })"
                        >
                            {{ $t('projects.add_task') }}
                        </at-button>
                    </div>

                    <div class="control-item">
                        <at-button size="large" @click="$router.push(`/projects/${$route.params['id']}/tasks/list`)">
                            {{ $t('control.task-list') }}
                        </at-button>
                    </div>

                    <div class="control-item">
                        <at-button size="large" @click="$router.go(-1)">{{ $t('control.back') }}</at-button>
                    </div>
                </div>
            </div>

            <div ref="kanban" class="project-tasks at-container">
                <kanban-board :stages="stages" :blocks="blocks" @update-block="updateBlock">
                    <div
                        v-for="stage in stages"
                        :slot="stage"
                        :key="`stage_${stage}`"
                        class="status"
                        :style="getHeaderStyle(stage)"
                    >
                        <h3>{{ stage }}</h3>
                    </div>

                    <div
                        v-for="block in blocks"
                        :slot="block.id"
                        :key="`block_${block.id}`"
                        class="task"
                        @click="loadTask(block.id)"
                    >
                        <h4 class="task-name">{{ getTask(block.id).task_name }}</h4>

                        <p class="task-description">{{ getTask(block.id).description }}</p>

                        <div class="task-users">
                            <team-avatars :users="getTask(block.id).users"></team-avatars>
                        </div>
                    </div>
                </kanban-board>
            </div>
        </div>

        <transition name="slide">
            <div v-if="task" class="task-view">
                <div>
                    <div class="task-view-header">
                        <h4 class="task-view-title">{{ task.task_name }}</h4>

                        <p class="task-view-description">{{ task.description }}</p>

                        <div class="task-view-close" @click="task = null">
                            <span class="icon icon-x"></span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.users') }}:</div>

                        <div class="col">
                            <team-avatars :users="task.users"></team-avatars>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.due_date') }}:</div>
                        <div class="col">{{ task.due_date ? formatDate(task.due_date) : '' }}</div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.total_spent') }}:</div>
                        <div class="col">
                            {{ task.total_spent_time ? formatDurationString(task.total_spent_time) : '' }}
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.priority') }}:</div>
                        <div class="col">{{ task.priority ? task.priority.name : '' }}</div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.source') }}:</div>
                        <div class="col">{{ task.project.source }}</div>
                    </div>

                    <div class="row">
                        <div class="col-10 label">{{ $t('field.created_at') }}:</div>
                        <div class="col">{{ formatDate(task.created_at) }}</div>
                    </div>
                </div>

                <div class="row">
                    <at-button
                        class="control-item"
                        size="large"
                        icon="icon-eye"
                        :title="$t('control.view')"
                        @click="viewTask(task)"
                    />

                    <at-button
                        v-if="$can('update', 'task', task)"
                        class="control-item"
                        size="large"
                        icon="icon-edit"
                        :title="$t('control.edit')"
                        @click="editTask(task)"
                    />

                    <at-button
                        v-if="$can('delete', 'task', task)"
                        class="control-item"
                        size="large"
                        type="error"
                        icon="icon-trash-2"
                        :title="$t('control.delete')"
                        @click="deleteTask(task)"
                    />
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
    import TeamAvatars from '@/components/TeamAvatars';
    import ProjectService from '@/services/resource/project.service';
    import StatusService from '@/services/resource/status.service';
    import TasksService from '@/services/resource/task.service';
    import { getTextColor } from '@/utils/color';
    import { formatDate, formatDurationString } from '@/utils/time';

    export default {
        components: {
            TeamAvatars,
        },
        name: 'Tasks',
        data() {
            return {
                projectService: new ProjectService(),
                statusService: new StatusService(),
                taskService: new TasksService(),
                project: {},
                statuses: [],
                tasks: [],
                task: null,
            };
        },
        computed: {
            stages() {
                return this.statuses.map(status => status.name);
            },
            blocks() {
                return this.tasks.map(task => ({
                    id: +task.id,
                    status: this.getStatusName(task.status_id),
                }));
            },
        },
        methods: {
            getTextColor,
            formatDate,
            formatDurationString,
            getBlock(id) {
                return this.blocks.find(block => +block.id === +id);
            },
            getTask(id) {
                return this.tasks.find(task => +task.id === +id);
            },
            getStatusByName(name) {
                return this.statuses.find(status => status.name === name);
            },
            getStatusName(id) {
                const status = this.statuses.find(status => +status.id === +id);
                if (status !== undefined) {
                    return status.name;
                }

                return '';
            },
            getHeaderStyle(name) {
                const status = this.getStatusByName(name);

                return {
                    background: status.color,
                    color: this.getTextColor(status.color),
                };
            },
            async updateBlock(blockId, newStatusName) {
                const block = this.getBlock(blockId);
                const newStatus = this.statuses.find(s => s.name === newStatusName);

                const blockElement = this.$refs.kanban.querySelector(`[data-block-id="${blockId}"]`);
                const prevBlockElement = blockElement.previousSibling;
                const nextBlockElement = blockElement.nextSibling;

                const prevBlockId = prevBlockElement ? +prevBlockElement.getAttribute('data-block-id') : 0;
                const prevTask = prevBlockId ? this.getTask(prevBlockId) : null;

                const nextBlockId = nextBlockElement ? +nextBlockElement.getAttribute('data-block-id') : 0;
                const nextTask = nextBlockId ? this.getTask(nextBlockId) : null;

                let newRelativePosition;
                if (prevTask !== null && nextTask !== null) {
                    newRelativePosition = (prevTask.relative_position + nextTask.relative_position) / 2;
                } else if (prevTask !== null) {
                    newRelativePosition = prevTask.relative_position + 1;
                } else if (nextTask !== null) {
                    newRelativePosition = nextTask.relative_position - 1;
                } else {
                    newRelativePosition = 0;
                }

                const task = this.getTask(blockId);

                const updatedTask = (
                    await this.taskService.save({
                        ...task,
                        users: task.users.map(user => +user.id),
                        status_id: newStatus.id,
                        relative_position: newRelativePosition,
                    })
                ).data.res;

                const taskIndex = this.tasks.findIndex(t => +t.id === +updatedTask.id);
                if (taskIndex !== -1) {
                    const tasks = [...this.tasks];
                    tasks.splice(taskIndex, 1, { ...task, ...updatedTask });
                    tasks.sort((a, b) => a.relative_position - b.relative_position);
                    this.tasks = tasks;
                }
            },
            async loadTask(id) {
                // Get basic task info from the task list
                this.task = this.getTask(id);

                // Load task details
                this.task = (
                    await this.taskService.getItem(id, {
                        with: 'users, priority, project',
                    })
                ).data;
            },
            viewTask(task) {
                this.$router.push({
                    path: `/projects/${task.project_id}/tasks/list/view/${task.id}`,
                });
            },
            editTask(task) {
                this.$router.push({
                    path: `/projects/${task.project_id}/tasks/list/edit/${task.id}`,
                });
            },
            async deleteTask(task) {
                const isConfirm = await this.$CustomModal({
                    title: this.$t('notification.record.delete.confirmation.title'),
                    content: this.$t('notification.record.delete.confirmation.message'),
                    okText: this.$t('control.delete'),
                    cancelText: this.$t('control.cancel'),
                    showClose: false,
                    styles: {
                        'border-radius': '10px',
                        'text-align': 'center',
                        footer: {
                            'text-align': 'center',
                        },
                        header: {
                            padding: '16px 35px 4px 35px',
                            color: 'red',
                        },
                        body: {
                            padding: '16px 35px 4px 35px',
                        },
                    },
                    width: 320,
                    type: 'trash',
                    typeButton: 'error',
                });

                if (isConfirm !== 'confirm') {
                    return;
                }

                await this.taskService.deleteItem(task.id);
                this.$Notify({
                    type: 'success',
                    title: this.$t('notification.record.delete.success.title'),
                    message: this.$t('notification.record.delete.success.message'),
                });

                this.task = null;

                const projectId = this.$route.params['id'];

                this.tasks = (
                    await this.taskService.getWithFilters({
                        project_id: projectId,
                        orderBy: 'relative_position',
                        with: 'users,priority',
                    })
                ).data;
            },
        },
        async created() {
            const projectId = this.$route.params['id'];

            this.project = (await this.projectService.getItem(projectId)).data;
            this.statuses = await this.statusService.getAll();

            this.tasks = (
                await this.taskService.getWithFilters({
                    project_id: projectId,
                    orderBy: 'relative_position',
                    with: 'users,priority',
                })
            ).data;
        },
        mounted() {
            if (this.$route.query.task) {
                this.loadTask(+this.$route.query.task);
            }
        },
    };
</script>

<style lang="scss" scoped>
    .at-container {
        position: relative;
    }

    .control-item:not(:last-child) {
        margin-right: 16px;
    }

    .status {
        width: 100%;
        padding: 16px;
        text-align: center;

        h3 {
            color: inherit;
        }
    }

    .task {
        background: #ffffff;
        padding: 16px;
        cursor: default;

        &-description {
            height: 24px;
            overflow: hidden;
        }

        &-users {
            display: flex;
            justify-content: flex-end;
            height: 34px;
        }
    }

    .task-view {
        position: fixed;
        top: 0;
        right: 0;
        display: flex;
        flex-flow: column nowrap;
        justify-content: space-between;
        background: #ffffff;
        border: 1px solid #c5d9e8;
        border-radius: 4px;
        width: 500px;
        height: 100vh;
        overflow: hidden auto;
        padding: 16px;

        &-header {
            position: relative;
            padding: 32px;
        }

        &-title {
            margin-bottom: 16px;
        }

        &-close {
            position: absolute;
            top: 0;
            right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            widows: 32px;
            height: 32px;
            cursor: pointer;
        }

        .row {
            margin: 0 32px;
            padding-bottom: 16px;
        }

        .row:not(:last-child) {
            border-bottom: 1px solid #eeeef5;
            margin-bottom: 16px;
        }

        .label {
            font-weight: bold;
        }
    }

    .project-tasks {
        padding: 16px;
    }

    .project-tasks /deep/ {
        ul.drag-list,
        ul.drag-inner-list {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        .drag-list {
            display: flex;
            align-items: stretch;
            min-height: calc(100vh - 250px);
        }

        .drag-column {
            flex: 1;
            position: relative;
            border: 1px solid #c5d9e8;
            border-radius: 4px;

            h2 {
                font-size: 0.8rem;
                margin: 0;
                text-transform: uppercase;
                font-weight: 600;
            }
        }

        .drag-column:not(:last-child) {
            margin-right: 16px;
        }

        .drag-column-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .drag-inner-list {
            min-height: 50px;
            height: 100%;
            color: white;
        }

        .drag-item {
            margin: 16px;
            border: 1px solid #c5d9e8;
            border-radius: 4px;
            transition: border 0.2s;
            overflow: hidden;

            &:hover {
                border-color: #79a1eb;
            }
        }

        .drag-header-more {
            cursor: pointer;
        }

        /* Dragula CSS  */

        .gu-mirror {
            position: fixed !important;
            margin: 0 !important;
            z-index: 9999 !important;
            opacity: 0.8;
            list-style-type: none;
        }

        .gu-hide {
            display: none !important;
        }

        .gu-unselectable {
            -webkit-user-select: none !important;
            -moz-user-select: none !important;
            -ms-user-select: none !important;
            user-select: none !important;
        }

        .gu-transit {
            opacity: 0.2;
        }
    }

    .slide-enter-active,
    .slide-leave-active {
        transition: transform 250ms ease;
    }
    .slide-enter,
    .slide-leave-to {
        transform: translate(100%, 0);
    }
</style>
