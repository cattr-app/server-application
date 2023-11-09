<template>
    <div class="container">
        <h1 class="page-title">{{ $t('navigation.offline_sync') }}</h1>
        <div class="at-container offline-sync">
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
                        >{{ $t('offline_sync.upload') }}
                    </at-button>
                </div>
            </div>
            <div class="row">
                <div class="offline-sync__added col-24">
                    <h5>{{ $t('offline_sync.added_intervals') }}</h5>
                    <at-table :columns="columns" :data="addedIntervals"></at-table>
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

    export default {
        name: 'Page',
        components: {
            ValidationObserver,
            ValidationProvider,
        },
        data() {
            return {
                addedIntervals: [],
                service: new OfflineSyncService(),
                columns: [
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
                            return h('div', [
                                h('i', {
                                    class: {
                                        icon: true,
                                        'icon-x-circle': !params.item.success,
                                        'icon-check-circle': params.item.success,
                                    },
                                }),
                                h('span', params.item.message),
                            ]);
                        },
                    },
                ],
            };
        },
        methods: {
            async uploadIntervals() {
                const file = this.$refs.intervals_file_input.$el.querySelector('input').files[0];
                const { valid } = await this.$refs.intervals_file.validate(file);

                if (valid) {
                    const result = await this.service.upload(file);
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
        },
    };
</script>

<style scoped lang="scss">
    .offline-sync {
        padding: 1rem 1.5rem 2.5rem;

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
        }

        &__added {
            margin-top: $spacing-03;
        }
    }
</style>
