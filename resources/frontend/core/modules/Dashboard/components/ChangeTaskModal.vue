<template>
    <at-modal :value="showModal" :title="$t('control.edit_intervals')" @on-cancel="cancel" @on-confirm="confirm">
        <validation-observer ref="form" v-slot="{}">
            <validation-provider
                ref="project"
                v-slot="{ errors }"
                rules="required"
                :name="$t('field.project')"
                mode="passive"
            >
                <div class="input-group">
                    <small>{{ $t('field.project') }}</small>

                    <resource-select
                        v-model="projectId"
                        class="input"
                        :service="projectsService"
                        :class="{ 'at-select--error': errors.length > 0 }"
                    />
                    <p>{{ errors[0] }}</p>
                </div>
            </validation-provider>

            <validation-provider
                ref="task"
                v-slot="{ errors }"
                rules="required"
                :name="$t('field.task')"
                mode="passive"
            >
                <div class="input-group">
                    <small>{{ $t('field.task') }}</small>

                    <at-select
                        v-if="enableTaskSelect"
                        v-model="taskId"
                        filterable
                        class="input"
                        :placeholder="$t('control.select')"
                        :class="{ 'at-select--error': errors.length > 0 }"
                    >
                        <at-option
                            v-for="option of tasksOptionList"
                            :key="option.value"
                            :value="option.value"
                            :label="option.label"
                        >
                            <div class="input__select-wrap">
                                <div class="flex flex-wrap flex-gap">
                                    <at-tooltip
                                        v-for="(user, userKey) in option.users"
                                        :key="userKey"
                                        :content="user.full_name"
                                        placement="right"
                                        class="user-tooltips"
                                    >
                                        <user-avatar :user="user" />
                                    </at-tooltip>
                                </div>
                                <span>{{ option.label }}</span>
                            </div>
                        </at-option>
                    </at-select>
                    <at-input v-else class="input" disabled />

                    <p>{{ errors[0] }}</p>
                </div>
            </validation-provider>
        </validation-observer>

        <div slot="footer">
            <at-button @click="cancel">{{ $t('control.cancel') }}</at-button>
            <at-button type="primary" :disabled="disableButtons" @click="confirm">{{ $t('control.save') }} </at-button>
        </div>
    </at-modal>
</template>

<script>
    import ResourceSelect from '@/components/ResourceSelect';
    import ProjectService from '@/services/resource/project.service';
    import TasksService from '@/services/resource/task.service';
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import UserAvatar from '@/components/UserAvatar';

    export default {
        name: 'ChangeTaskModal',
        components: {
            ResourceSelect,
            ValidationObserver,
            ValidationProvider,
            UserAvatar,
        },
        props: {
            showModal: {
                required: true,
                type: Boolean,
            },
            disableButtons: {
                default: false,
                type: Boolean,
            },
        },
        computed: {
            enableTaskSelect() {
                return !!(this.projectId && this.tasksOptionList);
            },
        },
        data() {
            return {
                projectId: '',
                taskId: '',

                projectsService: new ProjectService(),
                tasksService: new TasksService(),

                tasksOptionList: [],
            };
        },
        methods: {
            cancel() {
                this.$refs.form.reset();

                this.projectId = '';
                this.taskId = '';

                this.$emit('cancel');
            },

            async confirm() {
                const valid = await this.$refs.form.validate();
                if (!valid) {
                    return;
                }

                const { taskId } = this;

                this.projectId = '';
                this.taskId = '';

                this.$emit('confirm', taskId);
            },
        },
        watch: {
            async projectId(projectId) {
                try {
                    const taskList = (
                        await this.tasksService.getWithFilters({ where: { project_id: projectId }, with: ['users'] })
                    ).data.data;
                    this.tasksOptionList = taskList.map(option => ({
                        value: option.id,
                        label: option['task_name'],
                        users: option.users,
                    }));
                } catch ({ response }) {
                    if (process.env.NODE_ENV === 'development') {
                        console.warn(response ? response : 'Request to tasks is canceled');
                    }
                }

                requestAnimationFrame(() => {
                    if (Object.prototype.hasOwnProperty.call(this.$refs, 'project') && this.$refs.project) {
                        this.$refs.project.reset();
                    }

                    if (Object.prototype.hasOwnProperty.call(this.$refs, 'task') && this.$refs.project) {
                        this.$refs.task.reset();
                    }
                });
            },
        },
    };
</script>

<style lang="scss" scoped>
    .input-group {
        margin-bottom: $layout-01;
    }

    .input {
        margin-bottom: $spacing-02;

        &__select-wrap {
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;

            span {
                padding-left: 10px;
            }

            .flex {
                max-width: 40%;
            }
        }
    }
</style>
