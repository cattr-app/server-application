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
                        <at-button size="large" @click="$router.push({ name: 'Projects.crud.projects' })">
                            {{ $t('control.project-list') }}
                        </at-button>
                    </div>
                    <div class="control-item">
                        <at-button size="large" @click="$router.go(-1)">{{ $t('control.back') }}</at-button>
                    </div>
                </div>
            </div>
            <div ref="kanban" class="project-tasks_kanban at-container">
                <kanban-board
                    ref="board"
                    :config="config"
                    :stages="stages.map(s => s.name)"
                    :blocks="blocks"
                    @update-block="updateBlock"
                >
                    <div
                        v-for="stage in stages"
                        :slot="stage.name"
                        :key="`stage_${stage.name}`"
                        class="status"
                        :style="getHeaderStyle(stage.name)"
                    >
                        <at-button
                            v-if="stage.order !== 0"
                            type="text"
                            size="large"
                            class="button-kanban"
                            icon="icon-chevron-left"
                            :style="getHeaderStyle(stage.name)"
                            @click="changeOrder(stage.order, 'left')"
                        >
                        </at-button>
                        <h3>{{ stage.name }}</h3>
                        <at-button
                            v-if="stage.order !== stages.length - 1"
                            type="text"
                            size="large"
                            class="button-kanban"
                            icon="icon-chevron-right"
                            :style="getHeaderStyle(stage.name)"
                            @click="changeOrder(stage.order, 'right')"
                        >
                        </at-button>
                    </div>

                    <div
                        v-for="block in blocks"
                        :slot="block.id"
                        :key="`block_${block.id}`"
                        :class="{ task: true, handle: isDesktop }"
                        @click="loadTask(block.id)"
                        @mousedown="onDownMouse"
                        @touchstart="onDown"
                        @pointerdown="task = null"
                    >
                        <h4 class="task-name">{{ getTask(block.id).task_name }}</h4>
                        <at-tag class="tag-priority" :color="getTask(block.id).priority.color">{{
                            getTask(block.id).priority.name
                        }}</at-tag>
                        <p class="task-description" v-html="getTask(block.id).description"></p>
                        <div class="task-users">
                            <div class="task__tags">
                                <at-tag v-if="isOverDue(companyData.timezone, block)" color="error"
                                    >{{ $t('tasks.due_date--overdue') }}
                                </at-tag>
                                <at-tag v-if="isOverTime(block)" color="warning"
                                    >{{ $t('tasks.estimate--overtime') }}
                                </at-tag>
                            </div>
                            <span class="total-time-row">
                                <i class="icon icon-clock"></i>&nbsp; {{ block.estimate }} {{ $t(`control.of`) }}
                                {{ block.total_spent_time }}
                            </span>
                            <team-avatars :users="getTask(block.id).users"></team-avatars>
                        </div>
                        <at-button
                            type="primary"
                            hollow
                            circle
                            :class="{ 'hide-on-mobile': isDesktop, 'move-task': !isDesktop, handle: !isDesktop }"
                            @click.stop
                        ></at-button>
                    </div>
                </kanban-board>
            </div>
        </div>
        <div
            v-custom-scroll="{ targetSelector: '.drag-container' }"
            :class="{ 'scrollbar-top': true, 'hide-on-mobile': !isDesktop }"
        ></div>
        <transition name="slide">
            <div v-if="task" class="task-view">
                <div class="actions__toggle">
                    <div class="task-view-header">
                        <h4 class="task-view-title">{{ task.task_name }}</h4>

                        <p class="task-view-description" v-html="task.description"></p>

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
                            {{
                                task.total_spent_time
                                    ? formatDurationString(+task.total_spent_time + +task.total_offset)
                                    : ''
                            }}
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
    import moment from 'moment-timezone';
    import TeamAvatars from '@/components/TeamAvatars';
    import ProjectService from '@/services/resource/project.service';
    import StatusService from '@/services/resource/status.service';
    import TasksService from '@/services/resource/task.service';
    import { mapGetters } from 'vuex';
    import { getTextColor } from '@/utils/color';
    import { formatDate, formatDurationString } from '@/utils/time';
    import { throttle } from 'lodash';
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
                config: {
                    moves: function (el, container, handle) {
                        return handle.classList.contains('handle');
                    },
                },
                isDesktop: false,
                isMobile: false,
                scrollInterval: null,
                mouseX: null,
                isDragging: false,
                edgeThreshold: Math.min(window.innerWidth * 0.1, 100),
                maxScrollSpeed: window.innerWidth * 0.002,
            };
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            stages() {
                return this.statuses.map(status => ({ name: status.name, order: this.getStatusByOrder(status.id) }));
            },
            blocks() {
                return this.tasks.map(task => ({
                    id: +task.id,
                    estimate: formatDurationString(task.estimate),
                    status: this.getStatusName(task.status_id),
                    total_spent_time: formatDurationString(+task.total_spent_time + +task.total_offset),
                    total_spent_time_over: +task.total_spent_time + +task.total_offset,
                    estimate_over: task.estimate,
                    due_date: task.due_date,
                }));
            },
        },
        methods: {
            getTextColor,
            formatDate,
            formatDurationString,
            handleClick(e) {
                if (!e.target.closest('.actions__toggle')) {
                    if (e.target.closest('.task')) {
                        return;
                    }
                    this.task = null;
                }
            },
            isOverDue(companyTimezone, item) {
                return (
                    typeof companyTimezone === 'string' &&
                    item.due_date != null &&
                    moment.utc(item.due_date).tz(companyTimezone, true).isBefore(moment())
                );
            },
            checkScreenSize() {
                this.isDesktop = window.screen.width > 900;
                this.checkScreenSizeMobile();
            },
            checkScreenSizeMobile() {
                this.isMobile = window.screen.width < 600;
            },
            isOverTime(item) {
                return item.estimate_over != null && item.total_spent_time_over > item.estimate_over;
            },
            getBlock(id) {
                return this.blocks.find(block => +block.id === +id);
            },
            getTask(id) {
                return this.tasks.find(task => +task.id === +id);
            },
            getStatusByName(name) {
                return this.statuses.find(status => status.name === name);
            },
            getStatusByOrder(id) {
                const index = this.statuses.findIndex(item => item.id === id);
                return index;
            },
            getStatusName(id) {
                const status = this.statuses.find(status => +status.id === +id);
                if (status !== undefined) {
                    return status.name;
                }

                return '';
            },
            onDownMouse(e) {
                if (e.buttons & 1) {
                    document.addEventListener('mousemove', this.onMoveMouse);
                    document.addEventListener('mouseup', this.onUpMouse);
                    this.onScrollMouse();
                    this.scrollTimer = setInterval(this.onScrollMouse, 1);
                }
            },
            onMove(e) {
                this.mouseX = e.touches[0].clientX;
            },
            onMoveMouse(e) {
                this.mouseX = e.clientX;
            },
            onUpMouse(e) {
                document.removeEventListener('mousemove', this.onMoveMouse);
                document.removeEventListener('mouseup', this.onUpMouse);
                clearInterval(this.scrollTimer);
            },
            onDown(e) {
                document.addEventListener('touchmove', this.onMove);
                document.addEventListener('touchend', this.onUp);
                if (!this.isMobile) {
                    this.onScrollMouse();
                    this.scrollTimer = setInterval(this.onScrollMouse, 1);
                } else {
                    this.scrollTimer = setInterval(this.onScrollTimerTouch, 1);
                }
            },
            onScrollTimerTouch() {
                if (this.mouseX < 50) {
                    document.querySelector('.drag-container').scrollLeft -= 1;
                }
                if (this.mouseX > window.screen.width - 50) {
                    document.querySelector('.drag-container').scrollLeft += 1;
                }
            },
            onScrollMouse() {
                let scrollSpeed = 0;
                if (this.mouseX !== null && this.mouseX < this.edgeThreshold) {
                    scrollSpeed = this.maxScrollSpeed * (1 - this.mouseX / this.edgeThreshold);
                    document.querySelector('.drag-container').scrollBy(-scrollSpeed, 0);
                } else if (this.mouseX > window.innerWidth - this.edgeThreshold) {
                    const distanceToRightEdge = window.innerWidth - this.mouseX;
                    scrollSpeed = this.maxScrollSpeed * (1 - distanceToRightEdge / this.edgeThreshold);
                    document.querySelector('.drag-container').scrollBy(scrollSpeed, 0);
                }
            },
            onUp(e) {
                document.removeEventListener('touchmove', this.onMove);
                document.removeEventListener('touchend', this.onUp);
                clearInterval(this.scrollTimer);
            },
            onUpMove(e) {
                document.removeEventListener('mousemove', this.onMove);
                document.removeEventListener('mouseend', this.onUpMove);
                document.querySelector('.drag-container').removeEventListener('scroll', this.onDragContainerScroll);
                this.mouseX = null;
                clearInterval(this.scrollTimer);
            },
            changeOrder: throttle(async function (index, direction) {
                const service = this.statusService;
                const item = this.statuses[index];
                const targetIndex = direction === 'left' ? index - 1 : index + 1;
                const targetItem = this.statuses[targetIndex];

                await service.save({ ...targetItem, order: item.order });

                this.$set(this.statuses, index, { ...targetItem, order: item.order });
                this.$set(this.statuses, targetIndex, { ...item, order: targetItem.order });
            }, 1000),
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
                const updatedTask = await this.taskService.save({
                    ...task,
                    users: task.users.map(user => +user.id),
                    status_id: newStatus.id,
                    relative_position: newRelativePosition,
                });
                const taskIndex = this.tasks.findIndex(t => +t.id === +updatedTask.id);
                if (taskIndex !== -1) {
                    const tasks = [...this.tasks];
                    tasks.splice(taskIndex, 1, { ...task, ...updatedTask });
                    tasks.sort((a, b) => a.relative_position - b.relative_position);
                    this.tasks = tasks;
                }
            },
            async loadTask(id) {
                this.task = this.getTask(id);
                this.task = (
                    await this.taskService.getItem(id, {
                        with: ['users', 'priority', 'project', 'can'],
                        withSum: [
                            ['workers as total_spent_time', 'duration'],
                            ['workers as total_offset', 'offset'],
                        ],
                    })
                ).data.data;
            },
            viewTask(task) {
                this.$router.push({
                    name: 'Tasks.crud.tasks.view',
                    params: { id: task.id },
                });
            },
            editTask(task) {
                this.$router.push({
                    name: 'Tasks.crud.tasks.edit',
                    params: { id: task.id },
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
                    await this.taskService.getWithFilters(
                        {
                            where: { project_id: projectId },
                            orderBy: ['relative_position'],
                            with: ['users', 'priority', 'project', 'can'],
                            withSum: [
                                ['workers as total_spent_time', 'duration'],
                                ['workers as total_offset', 'offset'],
                            ],
                        },
                        { headers: { 'X-Paginate': 'false' } },
                    )
                ).data;
            },
        },
        async created() {
            const projectId = this.$route.params['id'];
            this.project = (await this.projectService.getItem(projectId)).data;
            this.statuses = (
                await this.statusService.getWithFilters({ orderBy: ['order'] }, { headers: { 'X-Paginate': 'false' } })
            ).data.data;
            this.tasks = (
                await this.taskService.getWithFilters(
                    {
                        where: { project_id: projectId },
                        orderBy: ['relative_position'],
                        with: ['users', 'priority', 'project', 'can'],
                        withSum: [
                            ['workers as total_spent_time', 'duration'],
                            ['workers as total_offset', 'offset'],
                        ],
                    },
                    { headers: { 'X-Paginate': 'false' } },
                )
            ).data.data;
        },
        mounted() {
            if (this.$route.query.task) {
                this.loadTask(+this.$route.query.task);
            }
            this.checkScreenSize();

            window.addEventListener('resize', this.checkScreenSize);
            window.addEventListener('click', this.handleClick);
            document.querySelector('.drag-container').addEventListener('scroll', this.onDragContainerScroll);
        },
        beforeDestroy() {
            window.removeEventListener('click', this.handleClick);
            window.removeEventListener('resize', this.checkScreenSize);
            window.removeEventListener('touchstart', this.onDown);
            window.removeEventListener('mousedown', this.onDownMouse);
            document.querySelector('.drag-container').removeEventListener('scroll', this.onDragContainerScroll);
        },
        directives: {
            customScroll: {
                bind: (el, binding) => {
                    const extraWidth = 100;
                    const scrollbar = document.createElement('div');
                    el.appendChild(scrollbar);
                    scrollbar.style.height = '20px';
                    const targetSelector = binding.value.targetSelector;
                    setTimeout(() => {
                        const width = document.querySelector(targetSelector).scrollWidth + 100;
                        if (scrollbar && width !== null) {
                            scrollbar.style.width = `${width}px`;
                        }
                    }, 1000);
                    el.addEventListener('scroll', e => {
                        const targetElement = document.querySelector(targetSelector);
                        if (targetElement) {
                            targetElement.scrollLeft = e.target.scrollLeft;
                        }
                    });
                },
            },
        },
    };
</script>

<style lang="scss" scoped>
    .task-view-description {
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        max-height: 290px;
    }
    .tag-priority {
        margin: 5px 0;
    }
    .crud__content {
        padding: 0 !important;
    }
    .scrollbar-top {
        overflow: scroll;
        width: 100%;
        height: 16px;
        position: fixed;
        bottom: 4px;
        left: 0;
        z-index: 1;
    }
    .handle {
        touch-action: none;
    }
    .task.handle > * {
        pointer-events: none;
    }
    .at-container {
        position: relative;
    }

    .control-item:not(:last-child) {
        margin-right: 16px;
    }

    .status {
        width: 100%;
        padding: 16px;
        display: flex;
        justify-content: space-between;
        min-width: 300px;
        align-items: flex-start;
        h3 {
            font-size: $font-size-lger;
            padding: 10px;
            flex: 1;
            color: inherit;
            text-align: center;
        }
    }

    .task {
        background: #ffffff;
        padding: 16px;
        cursor: default;
        overflow: hidden;
        &-description {
            height: 24px;
            overflow: hidden;
        }
        .move-task {
            display: flex;
            align-self: flex-start;
            color: $black-900;
            font-size: 1rem;
            font-weight: bold;
            padding: 10px;
        }
        &-users {
            display: flex;
            justify-content: flex-end;
            flex-wrap: wrap;
            align-items: center;
            margin-left: auto;
        }
    }
    .hide-on-mobile {
        display: none;
    }

    .task-view {
        position: fixed;
        bottom: 0;
        right: 0;
        display: flex;
        z-index: 1;
        flex-flow: column nowrap;
        justify-content: space-between;
        background: #ffffff;
        border: 1px solid #c5d9e8;
        border-radius: 4px;
        height: 100vh;
        overflow: hidden;
        padding: 16px;
        max-width: 500px;
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

    .project-tasks_kanban {
        padding: 16px;
        overflow-y: hidden;
    }

    .project-tasks_kanban ::v-deep {
        .drag-container {
            overflow: hidden;
        }
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
            flex-shrink: 0;
            flex-basis: 400px;
            max-width: 400px;
            position: relative;
            border: 1px solid #c5d9e8;
            border-radius: 6px;
            background-color: rgb(246, 248, 250);
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
            border-radius: 6px;
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
        .gu-mirror.withoutoffset {
            transform: translate(-50%, -50%);
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

    .total-time-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 21px;
        color: $black-900;
        font-size: 1rem;
        font-weight: bold;
        flex-wrap: nowrap;
    }
    .slide-enter-active,
    .slide-leave-active {
        transition: transform 250ms ease;
    }
    .slide-enter,
    .slide-leave-to {
        transform: translate(100%, 0);
    }
    .at-tag {
        vertical-align: middle;
        display: inline-flex;
        align-items: center;
    }
    .project-tasks_kanban {
        overflow-x: auto;
    }
    @media (max-width: 900px) {
        .crud {
            overflow: auto;
        }
        .project-tasks_kanban ::v-deep .drag-item {
            margin: 6px;
        }
        .scrollbar-top {
            display: none;
        }
        .project-tasks_kanban ::v-deep {
            .drag-container {
                overflow: auto;
            }
            scroll-snap-type: x mandatory;
        }
        .task ::v-deep {
            padding: 8px;
        }
        .task-view {
            padding-top: 60px;
            left: 0;
        }
        .task-name {
            font-size: $font-size-normal;
        }
        .task-description {
            font-size: $font-size-base;
        }
        .task-users {
            font-size: $font-size-smer;
        }
        .total-time-row {
            font-size: 10px;
        }

        .project-tasks_kanban ::v-deep .drag-column {
            scroll-snap-align: center;
            flex-shrink: 0;
        }
    }
    @media (max-width: $screen-sm) {
        .crud {
            overflow: auto;
        }
        .project-tasks_kanban ::v-deep .drag-item {
            margin: 4px;
        }
        .scrollbar-top {
            display: none;
        }
        .project-tasks_kanban ::v-deep {
            .drag-container {
                overflow: auto;
            }
            scroll-snap-type: x mandatory;
        }
        .task ::v-deep {
            padding: 8px;
        }
        .task-view {
            padding-top: 60px;
            width: auto;
            left: 0;
        }
        .task-name {
            font-size: $font-size-normal;
        }
        .task-description {
            font-size: $font-size-base;
        }
        .task-users {
            font-size: $font-size-smer;
        }
        .total-time-row {
            font-size: 10px;
        }
        .project-tasks_kanban ::v-deep .drag-column {
            scroll-snap-align: center;
            flex-shrink: 0;
        }
    }
    @media only screen and (max-width: $screen-xs) {
        .crud {
            overflow: auto;
        }
        .status {
            min-width: 100%;
        }
        .scrollbar-top {
            display: none;
        }
        .project-tasks_kanban ::v-deep .drag-item {
            margin: 2px;
        }
        .project-tasks_kanban ::v-deep {
            .drag-container {
                overflow: auto;
            }
            scroll-snap-type: x mandatory;
        }
        .task-view {
            padding-top: 60px;
            left: 0;
            max-width: 100%;
            width: 100%;
            margin: 0;
            border-radius: 0;
        }
        .task ::v-deep {
            padding: 8px;
        }
        .task-name {
            font-size: $font-size-normal;
        }
        .task-description {
            font-size: $font-size-base;
        }
        .task-users {
            font-size: $font-size-smer;
        }
        .total-time-row {
            font-size: 7px;
        }
        .project-tasks_kanban ::v-deep .drag-column {
            flex: 0 0 90%;
            margin-right: 10px;
            scroll-snap-align: center;
            max-width: 100%;
            // flex-basis: 100%;
            flex-shrink: 0;
        }
    }
    .button-kanban {
        aspect-ratio: 1;
        color: inherit;
    }
    .button-kanban:hover {
        color: #999 !important;
    }
</style>
