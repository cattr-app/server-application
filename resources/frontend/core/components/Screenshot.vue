<template>
    <div class="screenshot" @click="$emit('click', $event)">
        <div v-if="screenshotsEnabled && webcamEnabled" class="screenshot__tabs">
            <span
                class="screenshot__tab"
                :class="{ 'screenshot__tab--active': activeTab === 'screen' }"
                @click.stop="activeTab = 'screen'"
                >{{ $t('field.screen') }}</span
            >
            <span
                class="screenshot__tab"
                :class="{ 'screenshot__tab--active': activeTab === 'webcam' }"
                @click.stop="activeTab = 'webcam'"
                >{{ $t('field.webcam') }}</span
            >
        </div>

        <AppImage
            v-if="activeTab === 'screen' && screenshotsEnabled"
            :is-blob="true"
            :src="getThumbnailPath(interval)"
            class="screenshot__image"
            :lazy="lazyImage"
            @click="onShow"
        />
        <AppImage
            v-else-if="activeTab === 'webcam' && webcamEnabled && interval.has_webcam_screenshot"
            :is-blob="true"
            :src="getWebcamThumbnailPath(interval)"
            class="screenshot__image"
            :lazy="lazyImage"
            @click="onShow"
        />
        <i v-else class="icon icon-camera-off screenshot__image" />
        <at-tooltip>
            <template slot="content">
                <div v-if="interval.activity_fill === null" class="screenshot__activity">
                    {{ $t('tooltip.activity_progress.not_tracked') }}
                </div>
                <div v-else class="screenshot__activity">
                    <span v-if="interval.activity_fill !== null" class="screenshot__overall-activity">
                        {{
                            $tc('tooltip.activity_progress.overall', interval.activity_fill, {
                                percent: interval.activity_fill,
                            })
                        }}
                    </span>
                    <div class="screenshot__device-activity">
                        <span v-if="interval.mouse_fill !== null">
                            {{
                                $tc('tooltip.activity_progress.mouse', interval.mouse_fill, {
                                    percent: interval.mouse_fill,
                                })
                            }}
                        </span>
                        <span v-if="interval.keyboard_fill !== null">{{
                            $tc('tooltip.activity_progress.keyboard', interval.keyboard_fill, {
                                percent: interval.keyboard_fill,
                            })
                        }}</span>
                    </div>
                </div>
            </template>
            <at-progress
                class="screenshot__activity-bar"
                :stroke-width="5"
                :percent="+(+interval.activity_fill / 2 || 0)"
            />
        </at-tooltip>

        <div v-if="showText" class="screenshot__text">
            <span v-if="task && showTask" class="screenshot__task" :title="`${task.task_name} (${task.project.name})`">
                {{ task.task_name }} ({{ task.project.name }})
            </span>
            <span class="screenshot__time">{{ screenshotTime }}</span>
        </div>

        <ScreenshotModal
            v-if="!disableModal"
            :project="project"
            :interval="interval"
            :show="showModal"
            :showNavigation="showNavigation"
            :task="task"
            :user="user"
            :initialTab="activeTab"
            @close="onHide"
            @remove="onRemove"
            @showNext="$emit('showNext')"
            @showPrevious="$emit('showPrevious')"
        />
    </div>
</template>

<script>
    import moment from 'moment-timezone';
    import AppImage from './AppImage';
    import ScreenshotModal from './ScreenshotModal';
    import { mapGetters } from 'vuex';

    export function thumbnailPathProvider(interval) {
        return `time-intervals/${interval.id}/thumb`;
    }

    export function webcamThumbnailPathProvider(interval) {
        return `time-intervals/${interval.id}/webcam-thumb`;
    }

    export const config = { thumbnailPathProvider, webcamThumbnailPathProvider };

    export default {
        name: 'Screenshot',
        components: {
            AppImage,
            ScreenshotModal,
        },
        props: {
            interval: {
                type: Object,
            },
            project: {
                type: Object,
            },
            task: {
                type: Object,
            },
            user: {
                type: Object,
            },
            showText: {
                type: Boolean,
                default: true,
            },
            showTask: {
                type: Boolean,
                default: true,
            },
            showNavigation: {
                type: Boolean,
                default: false,
            },
            disableModal: {
                type: Boolean,
                default: false,
            },
            lazyImage: {
                type: Boolean,
                default: true,
            },
            timezone: {
                type: String,
            },
        },
        data() {
            return { showModal: false, activeTab: 'screen' };
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            ...mapGetters('screenshots', { screenshotsEnabled: 'enabled' }),
            ...mapGetters('webcam', { webcamEnabled: 'enabled' }),
            screenshotTime() {
                const timezone = this.timezone || this.companyData['timezone'];

                if (!timezone || !this.interval.start_at) {
                    return;
                }

                return moment
                    .utc(this.interval.start_at)
                    .tz(this.companyData['timezone'], true)
                    .tz(timezone)
                    .format('HH:mm');
            },
        },
        methods: {
            onShow() {
                if (this.disableModal) {
                    return;
                }

                this.showModal = true;
                this.$emit('showModalChange', true);
            },
            onHide() {
                this.showModal = false;
                this.$emit('showModalChange', false);
            },
            onRemove() {
                this.onHide();
                this.$emit('remove', this.interval);
            },
            getThumbnailPath(interval) {
                return config.thumbnailPathProvider(interval);
            },
            getWebcamThumbnailPath(interval) {
                return config.webcamThumbnailPathProvider(interval);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .screenshot {
        &__tabs {
            display: flex;
            margin-bottom: 4px;
        }

        &__tab {
            flex: 1;
            text-align: center;
            font-size: 11px;
            font-weight: 600;
            color: #59566e;
            cursor: pointer;
            padding: 2px 0;
            border-bottom: 2px solid transparent;

            &--active {
                color: #2e2ef9;
                border-bottom-color: #2e2ef9;
            }
        }

        &__image {
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            line-height: 0;
            overflow: hidden;

            &::v-deep {
                .pu-skeleton {
                    height: 100px;
                }

                img {
                    height: 150px;
                }
            }
        }

        .icon {
            font-size: 70px;
            display: flex;
            justify-content: center;
        }

        &__text {
            align-items: baseline;
            color: #59566e;
            display: flex;
            flex-flow: row nowrap;
            font-size: 11px;
            font-weight: 600;
            justify-content: space-between;
        }

        &__activity {
            text-align: center;
        }

        &__device-activity {
            white-space: nowrap;
        }

        &__task {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        &::v-deep {
            .at-tooltip {
                width: 100%;

                &__trigger {
                    width: 100%;
                }
            }

            .at-progress__text {
                display: none;
            }
        }
    }
</style>
