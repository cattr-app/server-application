<template>
    <div class="attachments-wrapper">
        <at-table :columns="columns" :data="rows"></at-table>
        <div v-if="showControls" class="row">
            <div class="upload-wrapper col-24">
                <validation-observer ref="form" v-slot="{}">
                    <validation-provider ref="file_validation_provider" v-slot="{ errors }" mode="passive">
                        <at-input ref="file_input" class="attachments-input" name="attachments-files" type="file" />
                        <p>{{ errors[0] }}</p>
                    </validation-provider>
                </validation-observer>
                <at-button
                    class="offline-sync__upload-btn"
                    size="large"
                    icon="icon-upload"
                    type="primary"
                    :loading="isLoading"
                    :disabled="isLoading"
                    @click="uploadFiles"
                    >{{ $t('attachments.upload') }}
                </at-button>
                <div v-if="uploadQueue.length > 0">
                    <h3>{{ $t('attachments.upload_queue') }}</h3>
                    <div v-for="(file, index) in uploadQueue" :key="index">
                        <span :class="{ 'upload-failed': file.errorOnUpload }">{{ index + 1 }}) {{ file.name }}</span>
                        <div v-if="currentUploadIndex === index && uploadProgress != null" class="file-upload-progress">
                            <at-progress :percent="uploadProgress.progress" :stroke-width="15" />
                            <span class="file-upload-progress__total">{{ uploadProgress.humanReadable }}</span>
                            <span class="file-upload-progress__speed">{{ uploadProgress.speed }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { humanFileSize } from '@/utils/file';
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import TasksService from '@/services/resource/task.service';
    import Vue from 'vue';
    import i18n from '@/i18n';
    import { hasRole } from '@/utils/user';

    const attachmentStatus = {
        NOT_ATTACHED: 0, // file just uploaded, attachmentable not set yet, not calculating hash
        PROCESSING: 1, //moving file to correct project folder and then calculating hash
        GOOD: 2, // file hash matched (on cron check and initial upload)
        BAD: 3, //file hash NOT matched (on cron check)
    };

    export default {
        name: 'Attachments',
        components: { ValidationObserver, ValidationProvider },
        props: {
            value: {
                type: Number,
                default: 0,
            },
            attachments: {
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
                    title: this.$t('attachments.status'),
                    render: (h, params) => {
                        const statusInfo = this.getAttachmentStatusInfo(params.item.status);
                        return h(
                            'at-tooltip',
                            {
                                attrs: {
                                    content: statusInfo.text,
                                    placement: 'top-left',
                                },
                            },
                            [
                                h('i', {
                                    class: {
                                        'status-icon': true,
                                        icon: true,
                                        [statusInfo.class]: true,
                                    },
                                }),
                            ],
                        );
                    },
                },
                {
                    title: i18n.t('field.user'),
                    render: (h, { item }) => {
                        if (!hasRole(this.$store.getters['user/user'], 'admin')) {
                            return h('span', item.user.full_name);
                        }

                        return h(
                            'router-link',
                            {
                                props: {
                                    to: {
                                        name: 'Users.crud.users.view',
                                        params: { id: item.user_id },
                                    },
                                },
                            },
                            item.user.full_name,
                        );
                    },
                },
                {
                    title: this.$t('attachments.name'),
                    render: (h, params) => {
                        return h(
                            'span',
                            {
                                class: {
                                    strikethrough: params.item?.toDelete ?? false,
                                },
                            },
                            [
                                h(
                                    'a',
                                    {
                                        on: {
                                            click: async e => {
                                                e.preventDefault();
                                                const url = await this.taskService.generateAttachmentTmpUrl(
                                                    params.item.id,
                                                    30,
                                                );
                                                window.open(url);
                                            },
                                        },
                                    },
                                    params.item.original_name,
                                ),
                            ],
                        );
                    },
                },
                {
                    title: this.$t('attachments.size'),
                    render: (h, params) => {
                        return h('span', humanFileSize(params.item.size, true));
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
                                type: params.item.toDelete ? 'warning' : 'error',
                                icon: params.item.toDelete ? 'icon-rotate-ccw' : 'icon-trash-2',
                                size: 'small',
                            },
                            on: {
                                click: async () => {
                                    this.$set(this.rows, params.item.index, {
                                        ...this.rows[params.item.index],
                                        toDelete: !params.item.toDelete,
                                    });
                                    this.$emit('change', this.rows);
                                },
                            },
                        });
                    },
                });
            } else {
                columns.push({
                    title: '',
                    width: '40',
                    render: (h, params) => {
                        return h(
                            'at-popover',
                            {
                                attrs: {
                                    placement: 'left',
                                    title: this.$i18n.t('attachments.create_tmp_url_for'),
                                },
                            },
                            [
                                h('AtButton', {
                                    props: {
                                        type: 'primary',
                                        icon: 'icon-external-link',
                                        size: 'smaller',
                                    },
                                }),
                                h('div', { class: 'tmp-link-popover', slot: 'content' }, [
                                    h(
                                        'AtButton',
                                        {
                                            props: {
                                                type: 'primary',
                                                size: 'smaller',
                                            },
                                            on: {
                                                click: async () => {
                                                    await this.handleTmpUrlCreation(params.item.id, 60 * 60);
                                                },
                                            },
                                        },
                                        `1${this.$t('time.h')}`,
                                    ),
                                    h(
                                        'AtButton',
                                        {
                                            props: {
                                                type: 'primary',
                                                size: 'smaller',
                                            },
                                            on: {
                                                click: async () => {
                                                    await this.handleTmpUrlCreation(params.item.id, 60 * 60 * 7);
                                                },
                                            },
                                        },
                                        `7${this.$t('time.h')}`,
                                    ),
                                    h(
                                        'AtButton',
                                        {
                                            props: {
                                                type: 'primary',
                                                size: 'smaller',
                                            },
                                            on: {
                                                click: async () => {
                                                    await this.handleTmpUrlCreation(params.item.id, 60 * 60 * 24 * 7);
                                                },
                                            },
                                        },
                                        `7${this.$t('time.d')}`,
                                    ),
                                    h(
                                        'AtButton',
                                        {
                                            props: {
                                                type: 'primary',
                                                size: 'smaller',
                                            },
                                            on: {
                                                click: async () => {
                                                    await this.handleTmpUrlCreation(params.item.id, 60 * 60 * 24 * 14);
                                                },
                                            },
                                        },
                                        `14${this.$t('time.d')}`,
                                    ),
                                ]),
                            ],
                        );
                    },
                });
            }

            return {
                columns,
                rows: this.attachments,
                files: [],
                taskService: new TasksService(),
                uploadProgress: null,
                uploadQueue: [],
                currentUploadIndex: null,
                isLoading: false,
            };
        },
        mounted() {
            if (this.showControls) {
                this.$refs.file_input.$el.querySelector('input').setAttribute('multiple', 'multiple');
            }
        },
        methods: {
            async handleTmpUrlCreation(attachmentUUID, seconds = null) {
                const url = await this.taskService.generateAttachmentTmpUrl(attachmentUUID, seconds);
                await this.setClipboard(window.location.origin + url);
                Vue.prototype.$Notify.success({
                    title: this.$i18n.t('attachments.tmp_url_created'),
                    message: this.$i18n.t('attachments.copied_to_clipboard'),
                    duration: 5000,
                });
            },
            async setClipboard(text) {
                const type = 'text/plain';
                const blob = new Blob([text], { type });
                const data = [new ClipboardItem({ [type]: blob })];
                await navigator.clipboard.write(data);
            },
            getAttachmentStatusInfo(status) {
                if (status === attachmentStatus.GOOD) {
                    return { class: 'icon-check-circle', text: this.$i18n.t('attachments.is_good') };
                }
                if (status === attachmentStatus.BAD) {
                    return { class: 'icon-slash', text: this.$i18n.t('attachments.is_bad') };
                }
                if (status === attachmentStatus.NOT_ATTACHED) {
                    return { class: 'icon-alert-circle', text: this.$i18n.t('attachments.is_not_attached') };
                }
                if (status === attachmentStatus.PROCESSING) {
                    return { class: 'icon-cpu', text: this.$i18n.t('attachments.is_processing') };
                }
            },
            async uploadFiles() {
                const files = this.$refs.file_input.$el.querySelector('input').files;
                this.uploadQueue = Array.from(files).map(file => ({
                    errorOnUpload: false,
                    name: file.name,
                    size: file.size,
                    type: file.type,
                }));
                const { valid } = await this.$refs.file_validation_provider.validate(files);
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    this.currentUploadIndex = i;
                    this.isLoading = true;
                    try {
                        const result = await this.taskService.uploadAttachment(file, this.onUploadProgress.bind(this));
                        if (result.success) {
                            this.rows.push(result.data);
                            this.$emit('change', this.rows);
                        } else {
                            this.$set(this.uploadQueue[i], 'errorOnUpload', true);
                        }
                    } catch (e) {
                        this.$set(this.uploadQueue[i], 'errorOnUpload', true);
                    }
                }
                this.isLoading = false;
            },
            onUploadProgress(progressEvent) {
                this.uploadProgress = {
                    progress: +(progressEvent.progress * 100).toFixed(2),
                    loaded: progressEvent.loaded,
                    total: progressEvent.total,
                    humanReadable: `${humanFileSize(progressEvent.loaded, true)} / ${humanFileSize(
                        progressEvent.total,
                        true,
                    )}`,
                    speed: `${progressEvent.rate ? humanFileSize(progressEvent.rate, true) : '0 kB'}/s`,
                };
            },
        },
        watch: {
            attachments: function (val) {
                this.rows = val;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .attachments-wrapper,
    .upload-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    ::v-deep {
        .tmp-link-popover {
            display: flex;
            gap: 0.1rem;
        }
        .strikethrough {
            text-decoration: line-through;
        }
        .status-icon {
            font-size: 1rem;
            &.icon-check-circle {
                color: $color-success;
            }
            &.icon-slash {
                color: $color-error;
            }
            &.icon-alert-circle {
                color: $color-info;
            }
            &.icon-cpu {
                color: $color-warning;
            }
        }
    }
    .upload-failed {
        color: $color-error;
    }
    .file-upload-progress {
        margin-top: 0.5rem;
        font-size: 0.8rem;
        &::v-deep .at-progress {
            display: flex;
            align-items: end;
            &-bar {
                flex-basis: 40%;
            }
        }
    }
</style>
