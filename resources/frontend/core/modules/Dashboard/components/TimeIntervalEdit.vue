<template>
    <div>
        <transition name="slide-up">
            <div v-if="intervals.length" class="time-interval-edit-panel">
                <div class="container-fluid">
                    <div class="row flex-middle flex-between">
                        <div class="col-4">
                            {{ $t('field.selected') }}:
                            <strong>{{ formattedTotalTime }}</strong>
                        </div>
                        <div class="col-12">
                            <div class="flex flex-end">
                                <at-button
                                    :disabled="disabledButtons"
                                    class="time-interval-edit-panel__btn"
                                    @click="openAddNewTaskModal"
                                >
                                    {{ $t('control.add_new_task') }}
                                </at-button>

                                <at-button
                                    :disabled="disabledButtons"
                                    class="time-interval-edit-panel__btn"
                                    @click="openChangeTaskModal"
                                >
                                    {{ $t('control.edit_intervals') }}
                                </at-button>

                                <at-button
                                    :disabled="disabledButtons"
                                    class="time-interval-edit-panel__btn"
                                    type="error"
                                    @click="deleteTimeIntervals"
                                >
                                    <i class="icon icon-trash" />
                                    {{ $t('control.delete') }}
                                </at-button>

                                <div class="divider" />

                                <at-button class="time-interval-edit-panel__btn" @click="$emit('close')">
                                    {{ $t('control.cancel') }}
                                </at-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <template v-if="showAddNewTaskModal">
            <AddNewTaskModal
                :disableButtons="disabledButtons"
                :showModal="showAddNewTaskModal"
                @cancel="onAddNewTaskModalCancel"
                @confirm="onAddNewTaskModalConfirm"
            />
        </template>

        <template v-if="showChangeTaskModal">
            <ChangeTaskModal
                :disableButtons="disabledButtons"
                :showModal="showChangeTaskModal"
                @cancel="onChangeTaskModalCancel"
                @confirm="onChangeTaskModalConfirm"
            />
        </template>
    </div>
</template>

<script>
    import moment from 'moment';
    import { mapGetters } from 'vuex';
    import AddNewTaskModal from './AddNewTaskModal';
    import ChangeTaskModal from './ChangeTaskModal';
    import TasksService from '@/services/resource/task.service';
    import TimeIntervalsService from '@/services/resource/time-interval.service';

    export default {
        name: 'TimeIntervalEdit',
        components: {
            AddNewTaskModal,
            ChangeTaskModal,
        },
        props: {
            intervals: {
                type: Array,
            },
        },
        computed: {
            ...mapGetters('user', ['user']),
            showAddNewTaskModal() {
                return this.modal === 'addNewTask';
            },
            showChangeTaskModal() {
                return this.modal === 'changeTask';
            },
            formattedTotalTime() {
                return moment
                    .utc(this.intervals.reduce((total, curr) => total + curr.duration * 1000, 0))
                    .format('HH:mm:ss');
            },
        },
        data() {
            return {
                tasksService: new TasksService(),
                timeIntervalsService: new TimeIntervalsService(),
                modal: '',
                disabledButtons: false,
            };
        },
        methods: {
            async saveTimeIntervals(data) {
                try {
                    this.disabledButtons = true;

                    await this.timeIntervalsService.bulkEdit(data);

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.save.success.title'),
                        message: this.$t('notification.screenshot.save.success.message'),
                    });

                    this.$emit('edit');

                    this.modal = '';
                    this.disabledButtons = false;
                } catch (e) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.save.error.title'),
                        message: this.$t('notification.screenshot.save.error.message'),
                    });

                    this.disabledButtons = false;
                }
            },
            async deleteTimeIntervals() {
                try {
                    this.disabledButtons = true;

                    await this.timeIntervalsService.bulkDelete({
                        intervals: this.intervals.map(el => el.id),
                    });

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.delete.success.title'),
                        message: this.$t('notification.screenshot.delete.success.message'),
                    });

                    this.$emit('remove', this.intervals);
                    this.disabledButtons = false;
                } catch (e) {
                    console.log(e);

                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.delete.error.title'),
                        message: this.$t('notification.screenshot.delete.error.message'),
                    });

                    this.disabledButtons = false;
                }
            },
            async createTask(projectId, taskName, taskDescription) {
                try {
                    this.disabledButtons = true;

                    const taskResponse = await this.tasksService.save(
                        {
                            project_id: projectId,
                            task_name: taskName,
                            description: taskDescription,
                            user_id: this.user.id,
                            active: true,
                            priority_id: 2,
                        },
                        true,
                    );

                    const task = taskResponse.data.res;
                    const intervals = this.intervals.map(i => ({
                        id: i.id,
                        task_id: task.id,
                    }));
                    await this.timeIntervalsService.bulkEdit({ intervals });

                    this.$Notify({
                        type: 'success',
                        title: this.$t('notification.screenshot.save.success.title'),
                        message: this.$t('notification.screenshot.save.success.message'),
                    });

                    this.$emit('edit');

                    this.modal = '';
                    this.disabledButtons = false;
                } catch (e) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('notification.screenshot.save.error.title'),
                        message: this.$t('notification.screenshot.save.error.message'),
                    });

                    this.disabledButtons = false;
                }
            },
            openAddNewTaskModal() {
                this.modal = 'addNewTask';
            },
            openChangeTaskModal() {
                this.modal = 'changeTask';
            },
            onAddNewTaskModalConfirm({ projectId, taskName, taskDescription }) {
                this.createTask(projectId, taskName, taskDescription);
            },
            onChangeTaskModalConfirm(taskId) {
                const intervals = this.intervals.map(i => ({ id: i.id, task_id: taskId }));
                this.saveTimeIntervals({ intervals });
            },
            onAddNewTaskModalCancel() {
                this.modal = '';
            },
            onChangeTaskModalCancel() {
                this.modal = '';
            },
        },
    };
</script>

<style lang="scss" scoped>
    .time-interval-edit-panel {
        border-top: 1px solid $gray-4;
        padding: 15px 0;
        position: fixed;
        z-index: 999;
        background-color: #fff;

        bottom: 0;
        right: 0;
        left: 0;

        &__btn {
            margin-right: $layout-01;

            &:last-child {
                margin-right: 0;
            }
        }
    }

    .divider {
        background-color: $gray-4;
        width: 1px;
        margin-right: $layout-01;
    }
</style>
