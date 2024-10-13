<template>
    <div class="container">
        <h1 class="page-title">{{ $t('navigation.offline_sync') }}</h1>
        <div class="at-container offline-sync">
            <div class="row">
                <div class="col-8">
                    <h2 class="page-title">{{ $t('offline_sync.projects_and_tasks') }}</h2>
                    <validation-observer ref="form" v-slot="{}">
                        <validation-provider
                            ref="user_select"
                            v-slot="{ errors }"
                            rules="required"
                            :name="$t('offline_sync.user')"
                            mode="passive"
                        >
                            <small>{{ $t('offline_sync.user') }}</small>

                            <resource-select
                                v-model="userId"
                                class="input"
                                :service="usersService"
                                :class="{ 'at-select--error': errors.length > 0 }"
                            />

                            <p>{{ errors[0] }}</p>
                        </validation-provider>
                    </validation-observer>
                    <at-button
                        class="offline-sync__upload-btn"
                        size="large"
                        icon="icon-download"
                        type="primary"
                        @click="exportTasks"
                        >{{ $t('offline_sync.export') }}
                    </at-button>
                </div>
            </div>
            <div class="row">
                <div class="col-8">
                    <h2 class="page-title">{{ $t('offline_sync.intervals') }}</h2>
                    <validation-observer ref="form" v-slot="{}">
                        <validation-provider
                            ref="intervals_file"
                            v-slot="{ errors }"
                            :rules="`required|ext:cattr|size:${12 * 1024}`"
                            :name="$t('offline_sync.intervals_file')"
                            mode="passive"
                        >
                            <at-input
                                ref="intervals_file_input"
                                class="intervals-input"
                                name="intervals-file"
                                type="file"
                            />
                            <p>{{ errors[0] }}</p>
                        </validation-provider>
                    </validation-observer>
                    <at-button
                        class="offline-sync__upload-btn"
                        size="large"
                        icon="icon-upload"
                        type="primary"
                        @click="uploadIntervals"
                        >{{ $t('offline_sync.import') }}
                    </at-button>
                </div>
            </div>
            <div class="row">
                <div class="offline-sync__added col-24">
                    <h5>{{ $t('offline_sync.added_intervals') }}</h5>
                    <at-table :columns="intervalsColumns" :data="addedIntervals"></at-table>
                </div>
            </div>
            <div class="row">
                <div class="col-8">
                    <h2 class="page-title">{{ $t('offline_sync.screenshots') }}</h2>
                    <validation-observer ref="form" v-slot="{}">
                        <validation-provider
                            ref="screenshots_file"
                            v-slot="{ errors }"
                            :rules="`required|ext:cattr`"
                            :name="$t('offline_sync.screenshots_file')"
                            mode="passive"
                        >
                            <at-input
                                ref="screenshots_file_input"
                                class="screenshots-input"
                                name="screenshots-file"
                                type="file"
                            />
                            <p>{{ errors[0] }}</p>
                        </validation-provider>
                    </validation-observer>
                    <at-button
                        class="offline-sync__upload-btn"
                        size="large"
                        icon="icon-upload"
                        type="primary"
                        @click="uploadScreenshots"
                        >{{ $t('offline_sync.import') }}
                    </at-button>
                    <div v-if="screenshotsUploadProgress != null" class="screenshots-upload-progress">
                        <at-progress :percent="screenshotsUploadProgress.progress" :stroke-width="15" />
                        <span class="screenshots-upload-progress__total">{{
                            screenshotsUploadProgress.humanReadable
                        }}</span>
                        <span class="screenshots-upload-progress__speed">{{ screenshotsUploadProgress.speed }}</span>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="offline-sync__added col-24">
                    <h5>{{ $t('offline_sync.added_screenshots') }}</h5>
                    <at-table :columns="screenshotsColumns" :data="addedScreenshots"></at-table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { ValidationObserver, ValidationProvider } from 'vee-validate';
    import OfflineSyncService from '../services/offline-sync.service';
    import { formatDurationString } from '@/utils/time';
    import moment from 'moment';
    import ResourceSelect from '@/components/ResourceSelect.vue';
    import UsersService from '@/services/resource/user.service';
    import { humanFileSize } from '@/utils/file';

    const formatImportResultMessage = (h, params) => {
        const getResultIcon = () => {
            return h('i', {
                class: {
                    icon: true,
                    'icon-x-circle': !params.item.success,
                    'icon-check-circle': params.item.success,
                },
            });
        };
        return typeof params.item.message === 'string'
            ? [h('span', [getResultIcon(h, params), params.item.message])]
            : Object.entries(params.item.message).map(([key, msg]) =>
                  h('span', [getResultIcon(h, params), `${key}: ${msg}`]),
              );
    };

    export default {
        name: 'Page',
        components: {
            ResourceSelect,
            ValidationObserver,
            ValidationProvider,
        },
        data() {
            return {
                addedIntervals: [],
                addedScreenshots: [],
                service: new OfflineSyncService(),
                intervalsColumns: [
                    {
                        title: this.$t('offline_sync.user'),
                        render: (h, params) => {
                            return h(
                                'a',
                                {
                                    attrs: {
                                        href: `mailto:${params.item.user.email}`,
                                        target: '_blank',
                                    },
                                },
                                params.item.user.full_name,
                            );
                        },
                    },
                    {
                        title: this.$t('offline_sync.task_id'),
                        render: (h, params) => {
                            return h(
                                'router-link',
                                {
                                    props: {
                                        to: {
                                            name: `Tasks.crud.tasks.view`,
                                            params: { id: params.item.task_id },
                                        },
                                    },
                                },
                                params.item.task_id,
                            );
                        },
                    },
                    {
                        title: this.$t('offline_sync.start_at'),
                        key: 'start_at',
                    },
                    {
                        title: this.$t('offline_sync.end_at'),
                        key: 'end_at',
                    },
                    {
                        title: this.$t('offline_sync.total_time'),
                        key: 'total_time',
                    },
                    {
                        title: this.$t('offline_sync.result'),
                        render: (h, params) => {
                            return h(
                                'div',
                                { class: 'offline-sync__import-result' },
                                formatImportResultMessage(h, params),
                            );
                        },
                    },
                ],
                screenshotsColumns: [
                    {
                        title: this.$t('offline_sync.user'),
                        render: (h, params) => {
                            return h('span', params.item?.user_id ?? '-');
                        },
                    },
                    {
                        title: this.$t('offline_sync.task_id'),
                        render: (h, params) => {
                            return h(
                                params.item?.task_id ? 'router-link' : 'span',
                                {
                                    props: {
                                        to: {
                                            name: `Tasks.crud.tasks.view`,
                                            params: { id: params.item?.task_id },
                                        },
                                    },
                                },
                                params.item?.task_id ?? '-',
                            );
                        },
                    },
                    {
                        title: this.$t('offline_sync.start_at'),
                        key: 'start_at',
                    },
                    {
                        title: this.$t('offline_sync.end_at'),
                        key: 'end_at',
                    },
                    {
                        title: this.$t('offline_sync.total_time'),
                        key: 'total_time',
                    },
                    {
                        title: this.$t('offline_sync.result'),
                        render: (h, params) => {
                            return h(
                                'div',
                                { class: 'offline-sync__import-result' },
                                formatImportResultMessage(h, params),
                            );
                        },
                    },
                ],
                userId: null,
                usersService: new UsersService(),
                screenshotsUploadProgress: null,
            };
        },
        methods: {
            async exportTasks() {
                const { valid } = await this.$refs.user_select.validate(this.userId);
                if (valid) {
                    const result = await this.service.download(this.userId);

                    const blob = new Blob([result]);

                    const aElement = document.createElement('a');
                    aElement.setAttribute('download', 'ProjectsAndTasks.cattr');
                    const href = URL.createObjectURL(blob);
                    aElement.href = href;
                    aElement.setAttribute('target', '_blank');
                    aElement.click();
                    URL.revokeObjectURL(href);
                }
            },
            async uploadIntervals() {
                const file = this.$refs.intervals_file_input.$el.querySelector('input').files[0];
                const { valid } = await this.$refs.intervals_file.validate(file);

                if (valid) {
                    const result = await this.service.uploadIntervals(file);
                    if (result.success) {
                        this.addedIntervals = result.data.map(el => {
                            const timeDiff = moment(el.interval['end_at']).diff(moment(el.interval['start_at'])) / 1000;
                            const totalTime = Math.round((timeDiff + Number.EPSILON) * 100) / 100;

                            return {
                                ...el.interval,
                                message: el.message,
                                success: el.success,
                                total_time: formatDurationString(totalTime),
                            };
                        });
                    } else {
                        this.addedIntervals = [];
                    }
                }
            },
            async uploadScreenshots() {
                const file = this.$refs.screenshots_file_input.$el.querySelector('input').files[0];
                const { valid } = await this.$refs.screenshots_file.validate(file);

                if (valid) {
                    const result = await this.service.uploadScreenshots(file, this.onUploadProgress.bind(this));
                    if (result.success) {
                        this.addedScreenshots = result.data.map(el => {
                            const timeDiff = el.interval
                                ? moment(el.interval['end_at']).diff(moment(el.interval['start_at'])) / 1000
                                : '-';
                            const totalTime = el.interval ? Math.round((timeDiff + Number.EPSILON) * 100) / 100 : '-';

                            return {
                                ...el.interval,
                                message: el.message,
                                success: el.success,
                                total_time: el.interval ? formatDurationString(totalTime) : '-',
                            };
                        });
                    } else {
                        this.addedScreenshots = [];
                    }
                }
            },
            onUploadProgress(progressEvent) {
                this.screenshotsUploadProgress = {
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
    };
</script>

<style scoped lang="scss">
    .offline-sync {
        padding: 1rem 1.5rem 2.5rem;

        .row {
            margin-bottom: $spacing-05;
        }

        .intervals-input {
            width: 100%;
        }

        &__upload-btn {
            margin-top: $spacing-03;
        }

        &::v-deep {
            .page-title {
                color: $gray-1;
                font-size: 24px;
                margin-bottom: 0;
            }

            .icon {
                margin-right: 0.2rem;
            }
            .icon-x-circle {
                color: $red-1;
            }
            .icon-check-circle {
                color: $green-1;
            }
            .offline-sync__import-result {
                display: flex;
                flex-direction: column;
            }
        }

        &__added {
            margin-top: $spacing-03;
        }

        .screenshots-upload-progress {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            &::v-deep .at-progress {
                display: flex;
                align-items: end;
                &-bar {
                    flex-basis: 70%;
                }
            }
        }
    }
</style>
