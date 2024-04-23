<template>
    <div class="relations">
        <h5 class="relations__title">{{ $t('tasks.relations.follows') }}</h5>
        <at-table :columns="columns" :data="follows" size="small"></at-table>
        <h5 class="relations__title">{{ $t('tasks.relations.precedes') }}</h5>
        <at-table :columns="columns" :data="precedes" size="small"></at-table>

        <div v-if="this.showControls" class="relations__actions">
            <at-select v-model="relationType" :placeholder="$t('tasks.relations.type')">
                <at-option value="follows">{{ $t('tasks.relations.follows') }}</at-option>
                <at-option value="precedes">{{ $t('tasks.relations.precedes') }}</at-option>
            </at-select>
            <task-selector :value="selectedTask" :project-id="projectId" @change="handleTaskSelection" />
            <at-button :disabled="addBtnDisabled" class="relations__add-btn" type="success" @click="addRelation">{{
                $t('field.add_phase')
            }}</at-button>
        </div>
    </div>
</template>

<script>
    import TaskSelector from './TaskSelector.vue';

    export default {
        name: 'RelationsSelector',
        components: { TaskSelector },
        props: {
            parents: {
                type: Array,
                required: true,
            },
            children: {
                type: Array,
                required: true,
            },
            projectId: {
                type: Number,
                default: null,
            },
            showControls: {
                type: Boolean,
                default: true,
            },
        },
        data() {
            const columns = [
                {
                    title: this.$t('field.name'),
                    render: (h, params) => {
                        return h(
                            'router-link',
                            {
                                props: {
                                    to: {
                                        name: this.showControls ? 'Tasks.relations' : 'Tasks.crud.tasks.view',
                                        params: { id: params.item.id },
                                    },
                                },
                            },
                            params.item.task_name,
                        );
                    },
                },
            ];
            if (this.showControls) {
                columns.push({
                    title: '',
                    width: '40',
                    render: (h, params) => {
                        return h('AtButton', {
                            props: {
                                type: 'error',
                                icon: 'icon-trash-2',
                                size: 'smaller',
                            },
                            on: {
                                click: async () => {
                                    if (this.modalIsOpen) {
                                        return;
                                    }
                                    this.modalIsOpen = true;
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
                                    this.modalIsOpen = false;
                                    if (isConfirm === 'confirm') {
                                        this.$emit('unlink', params.item);
                                    }
                                },
                            },
                        });
                    },
                });
            }
            return {
                modalIsOpen: false,
                columns,
                follows: this.parents,
                precedes: this.children,
                relationType: '',
                tasks: [],
                selectedTask: '',
            };
        },
        methods: {
            handleTaskSelection(value) {
                this.selectedTask = value;
            },
            addRelation() {
                this.$emit('createRelation', {
                    taskId: +this.selectedTask,
                    type: this.relationType,
                });
            },
        },
        computed: {
            addBtnDisabled() {
                return !(this.selectedTask && this.relationType);
            },
        },
        watch: {
            parents(newVal) {
                this.follows = newVal;
            },
            children(newVal) {
                this.precedes = newVal;
            },
        },
    };
</script>

<style scoped lang="scss">
    .relations {
        &__title {
            margin-bottom: $spacing-01;
            &:last-of-type {
                margin-top: $spacing-03;
            }
            @media (min-width: 768px) {
                font-size: 0.8rem;
            }
        }
        &::v-deep {
            .at-input__original {
                border: none;
                width: 100%;
                font-size: 0.8rem;
            }
        }

        &__actions {
            display: flex;
            margin-top: $spacing-03;
            column-gap: $spacing-03;
            .at-select {
                max-width: 120px;
            }
            .task-select {
                flex-basis: 100%;
                &::v-deep ul {
                    overflow-x: hidden;
                }
            }
        }
    }
</style>
