<template>
    <at-modal v-if="show" class="modal" :width="900" :value="true" @on-cancel="onClose" @on-confirm="onClose">
        <template v-slot:header>
            <span class="modal-title">{{ $t('field.screenshot') }}</span>
            <div v-if="screenshotsEnabled && webcamEnabled" class="modal-tabs">
                <span
                    class="modal-tab"
                    :class="{ 'modal-tab--active': activeTab === 'screen' }"
                    @click="activeTab = 'screen'"
                    >{{ $t('field.screen') }}</span
                >
                <span
                    class="modal-tab"
                    :class="{ 'modal-tab--active': activeTab === 'webcam' }"
                    @click="activeTab = 'webcam'"
                    >{{ $t('field.webcam') }}</span
                >
            </div>
        </template>

        <AppImage
            v-if="activeTab === 'screen' && interval && interval.id && screenshotsEnabled"
            class="modal-screenshot"
            :src="getScreenshotPath(interval)"
            :openable="true"
        />
        <AppImage
            v-else-if="
                activeTab === 'webcam' && interval && interval.id && webcamEnabled && interval.has_webcam_screenshot
            "
            class="modal-screenshot"
            :src="getWebcamPath(interval)"
            :openable="true"
        />
        <i v-else class="icon icon-camera-off modal-screenshot" />
        <at-progress
            class="screenshot__activity-bar"
            :stroke-width="7"
            :percent="+(+interval.activity_fill / 2 || 0)"
        />

        <div v-if="showNavigation" class="modal-left">
            <at-button type="primary" icon="icon-arrow-left" @click="$emit('showPrevious')"></at-button>
        </div>

        <div v-if="showNavigation" class="modal-right">
            <at-button type="primary" icon="icon-arrow-right" @click="$emit('showNext')"></at-button>
        </div>

        <template v-slot:footer>
            <div class="row">
                <div class="col">
                    <div v-if="project" class="modal-field">
                        <span class="modal-label">{{ $t('field.project') }}:</span>
                        <span class="modal-value">
                            <router-link :to="`/projects/view/${project.id}`">{{ project.name }}</router-link>
                        </span>
                    </div>

                    <div v-if="task" class="modal-field">
                        <span class="modal-label">{{ $t('field.task') }}:</span>
                        <span class="modal-value">
                            <router-link :to="`/tasks/view/${task.id}`">{{ task.task_name }}</router-link>
                        </span>
                    </div>

                    <div v-if="user" class="modal-field">
                        <span class="modal-label">{{ $t('field.user') }}:</span>
                        <span class="modal-value">
                            {{ user.full_name }}
                        </span>
                    </div>

                    <div v-if="interval" class="modal-field">
                        <span class="modal-label">{{ $t('field.created_at') }}:</span>
                        <span class="modal-value">{{ formatDate(interval.start_at) }}</span>
                    </div>
                </div>
                <div class="col">
                    <div v-if="interval.activity_fill === null" class="screenshot__activity">
                        {{ $t('tooltip.activity_progress.not_tracked') }}
                    </div>
                    <div v-else class="screenshot__activity modal-field">
                        <div class="modal-field">
                            <span class="modal-label">{{ $tc('tooltip.activity_progress.overall', 0) }}</span>
                            <span class="modal-value">
                                {{ interval.activity_fill + '%' }}
                            </span>
                        </div>

                        <div v-if="interval.mouse_fill !== null" class="modal-field">
                            <span class="modal-label">
                                {{ $t('tooltip.activity_progress.just_mouse') }}
                            </span>

                            <span class="modal-value">
                                {{ interval.mouse_fill + '%' }}
                            </span>
                        </div>
                        <div v-if="interval.keyboard_fill !== null" class="modal-field">
                            <span class="modal-label">{{ $t('tooltip.activity_progress.just_keyboard') }}</span>
                            <span class="modal-value">
                                {{ interval.keyboard_fill + '%' }}
                            </span>
                        </div>
                    </div>
                    <div v-if="interval" class="modal-duration modal-field">
                        <span class="modal-label">{{ $t('field.duration') }}:</span>
                        <span class="modal-value">{{
                            $t('field.duration_value', [formatDate(interval.start_at), formatDate(interval.end_at)])
                        }}</span>
                    </div>
                </div>
            </div>
            <div v-if="canRemove" class="row">
                <at-button class="modal-remove" type="text" icon="icon-trash-2" @click="onRemove" />
            </div>
        </template>
    </at-modal>
</template>

<script>
    import moment from 'moment-timezone';
    import AppImage from './AppImage';
    import { mapGetters } from 'vuex';

    export function screenshotPathProvider(interval) {
        return `time-intervals/${interval.id}/screenshot`;
    }

    export function webcamPathProvider(interval) {
        return `time-intervals/${interval.id}/webcam`;
    }

    export const config = { screenshotPathProvider, webcamPathProvider };

    export default {
        name: 'ScreenshotModal',
        components: {
            AppImage,
        },
        props: {
            show: {
                type: Boolean,
                required: true,
            },
            project: {
                type: Object,
            },
            task: {
                type: Object,
            },
            interval: {
                type: Object,
            },
            user: {
                type: Object,
            },
            showNavigation: {
                type: Boolean,
                default: false,
            },
            canRemove: {
                type: Boolean,
                default: true,
            },
            initialTab: {
                type: String,
                default: 'screen',
            },
        },
        data() {
            return { activeTab: this.initialTab };
        },
        watch: {
            initialTab(val) {
                this.activeTab = val;
            },
        },
        computed: {
            ...mapGetters('user', ['companyData']),
            ...mapGetters('screenshots', { screenshotsEnabled: 'enabled' }),
            ...mapGetters('webcam', { webcamEnabled: 'enabled' }),
        },
        methods: {
            formatDate(value) {
                return moment
                    .utc(value)
                    .tz(this.companyData.timezone, true)
                    .locale(this.$i18n.locale)
                    .format('MMMM D, YYYY — HH:mm:ss (Z)');
            },
            onClose() {
                this.$emit('close');
            },
            onRemove() {
                this.$emit('remove', this.interval.id);
            },
            getScreenshotPath(interval) {
                return config.screenshotPathProvider(interval);
            },
            getWebcamPath(interval) {
                return config.webcamPathProvider(interval);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .modal {
        &::v-deep {
            .pu-skeleton {
                height: 70vh;
            }

            .at-modal__mask {
                background: rgba(#151941, 0.7);
            }

            .at-modal__wrapper {
                display: flex;
                align-items: center;
                justify-content: center;

                overflow-y: scroll;
                padding-top: 1rem;
                padding-bottom: 1rem;
            }

            .at-modal {
                border-radius: 15px;
                top: unset;
                height: fit-content;
            }

            .at-modal__header {
                border: 0;
            }

            .at-modal__body {
                padding: 0;
                position: relative;

                .icon-camera-off {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    font-size: 200px;
                }
            }

            .at-modal__footer {
                position: relative;
                border: 0;
                text-align: left;
            }

            .at-modal__close {
                color: #b1b1be;
            }

            .at-progress-bar {
                display: block;
                &__wraper,
                &__inner {
                    border-radius: 0;
                }
            }

            .at-progress__text {
                display: none;
            }
        }

        &-left {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            display: flex;
            align-items: center;
        }

        &-right {
            position: absolute;
            top: 0;
            right: 0;
            height: 100%;
            display: flex;
            align-items: center;
        }

        &-title {
            color: #000000;
            font-size: 15px;
            font-weight: 600;
        }

        &-tabs {
            display: inline-flex;
            margin-left: 16px;
            gap: 12px;
        }

        &-tab {
            font-size: 13px;
            font-weight: 600;
            color: #b1b1be;
            cursor: pointer;
            padding-bottom: 2px;
            border-bottom: 2px solid transparent;

            &--active {
                color: #2e2ef9;
                border-bottom-color: #2e2ef9;
            }
        }

        &-screenshot {
            display: block;

            width: 100%;
            height: auto;
            min-height: 300px;
            max-height: 70vh;

            object-fit: contain;
            object-position: center;

            margin: 0 auto;
        }

        &-remove {
            position: absolute;

            bottom: 12px;
            right: 16px;

            color: #ff5569;
        }

        &-field {
            color: #666;
            font-size: 15px;
            font-weight: 600;

            &:not(:last-child) {
                margin-bottom: 11px;
            }
        }

        &-label {
            margin-right: 0.5em;
        }

        &-value,
        &-value a {
            color: #2e2ef9;
        }

        &-duration {
            padding-right: 3em;
        }
    }
    @media (max-width: 500px) {
        .modal ::v-deep .at-modal__wrapper {
            align-items: start;
        }
    }
</style>
