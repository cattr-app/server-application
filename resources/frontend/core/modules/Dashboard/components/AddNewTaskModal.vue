<template>
    <at-modal :value="showModal" :title="$t('control.add_new_task')" @on-cancel="cancel" @on-confirm="confirm">
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
                ref="taskName"
                v-slot="{ errors }"
                rules="required"
                :name="$t('field.task_name')"
                mode="passive"
            >
                <div class="input-group">
                    <small>{{ $t('field.task_name') }}</small>

                    <at-input v-model="taskName" class="input" />

                    <p>{{ errors[0] }}</p>
                </div>
            </validation-provider>

            <validation-provider
                ref="taskDescription"
                v-slot="{ errors }"
                rules="required"
                :name="$t('field.task_description')"
                mode="passive"
            >
                <div class="input-group">
                    <small>{{ $t('field.task_description') }}</small>

                    <at-textarea v-model="taskDescription" class="input" />

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

    export default {
        name: 'AddNewTaskModal',
        components: {
            ResourceSelect,
            ValidationObserver,
            ValidationProvider,
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
        data() {
            return {
                projectId: '',
                taskName: '',
                taskDescription: '',

                projectsService: new ProjectService(),
                tasksService: new TasksService(),
            };
        },
        methods: {
            cancel() {
                this.$refs.form.reset();

                this.projectId = '';
                this.taskName = '';
                this.taskDescription = '';

                this.$emit('cancel');
            },

            async confirm() {
                const valid = await this.$refs.form.validate();
                if (!valid) {
                    return;
                }

                const { projectId, taskName, taskDescription } = this;

                this.projectId = '';
                this.taskName = '';
                this.taskDescription = '';

                this.$emit('confirm', { projectId, taskName, taskDescription });
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
    }
</style>
