<template>
    <div class="phases">
        <at-table :columns="columns" :data="rows"></at-table>
        <at-button v-if="this.showControls" class="phases__add-btn" type="primary" @click="addPhase">{{
            $t('field.add_phase')
        }}</at-button>
    </div>
</template>

<script>
    export default {
        name: 'Phases',
        props: {
            phases: {
                type: Array,
                required: true,
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
                        return this.showControls
                            ? h('AtInput', {
                                  props: {
                                      type: 'text',
                                      value: params.item.name,
                                  },
                                  on: {
                                      input: (value, b) => {
                                          this.rows[params.item.index]['name'] = value;
                                          this.$emit('change', this.rows);
                                      },
                                  },
                              })
                            : h('span', params.item.name);
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
                                size: 'small',
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
                                        this.rows.splice(params.item.index, 1);
                                        this.$emit('change', this.rows);
                                    }
                                },
                            },
                        });
                    },
                });
            } else {
                columns.push({
                    title: this.$tc('field.amount_of_tasks'),
                    render: (h, params) => {
                        return h(
                            'span',
                            this.$tc('projects.amount_of_tasks', params.item?.tasks_count ?? 0, {
                                count: params.item?.tasks_count,
                            }),
                        );
                    },
                });
            }
            return {
                modalIsOpen: false,
                columns,
                rows: this.phases,
            };
        },
        methods: {
            addPhase() {
                this.rows.push({
                    name: '',
                });
                this.$emit('change', this.rows);
            },
        },
        watch: {
            phases: function (val) {
                this.rows = val;
            },
        },
    };
</script>

<style scoped lang="scss">
    .phases {
        &::v-deep {
            .at-input__original {
                border: none;
                width: 100%;
            }
        }
        &__add-btn {
            margin-top: $spacing-03;
        }
    }
</style>
