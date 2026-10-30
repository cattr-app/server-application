<template>
    <div class="col-offset-9">
        <template v-if="storage">
            <p class="row">
                <span v-t="'about.storage.space.used'" class="col-6" />
                <at-progress
                    :percent="storageSpaceUsed"
                    :status="storageSpaceUsed < storageSpaceMaxUsed ? 'error' : 'default'"
                    :stroke-width="15"
                    :title="getSize(storage.space.used)"
                    class="col-4"
                />
                <span class="col-1" v-html="`${storageSpaceUsed}%`" />
            </p>
            <p class="row">
                <span v-t="'about.storage.space.total'" class="col-6" />
                <span class="col-4">
                    <at-tag>{{ getSize(storage.space.total) }}</at-tag>
                </span>
            </p>
            <p class="row">
                <span v-t="'about.storage.space.left'" class="col-6" />
                <span class="col-4">
                    <at-tag>{{ getSize(storage.space.left) }}</at-tag>
                </span>
            </p>
            <p class="row">
                <span v-t="'about.storage.last_thinning'" class="col-6" />
                <span class="col-4">
                    <at-tag :title="storageCleanTime">{{ storageRelativeCleanTime }}</at-tag>
                </span>
            </p>
            <p class="row">
                <span v-t="'about.storage.screenshots_available'" class="col-6" />
                <span class="col-4">
                    <at-tag>{{
                        $tc('about.storage.screenshots', storage.screenshots_available, {
                            n: storage.screenshots_available,
                        })
                    }}</at-tag>
                </span>
            </p>
            <p v-if="cleanupStatusVisible" class="row">
                <span v-t="'about.storage.cleanup_status'" class="col-6" />
                <span class="col-4">
                    <at-tag :color="cleanupStatusColor">{{ $t(`about.storage.status.${cleanupStatus}`) }}</at-tag>
                </span>
            </p>
            <p v-if="pollFailed" v-t="'about.storage.status.unavailable'" class="row storage__warning" />
            <p class="row">
                <at-button
                    :disabled="cleanupBusy || !storage.screenshots_available"
                    :loading="cleanupBusy"
                    :title="cleanButtonTitle"
                    class="col-10"
                    hollow
                    @click="cleanStorage"
                >
                    {{ cleanupBusy ? '' : $t('about.storage.thin') }}
                </at-button>
            </p>
        </template>
        <p v-else v-t="'about.no_storage'" />
    </div>
</template>
<script>
    import moment from 'moment';
    import AboutService from '@/services/resource/about.service';
    import { cleanupStatus, createStoragePoller, finishedCleanupStatus, isCleanupActive } from '@/utils/storageCleanup';

    const aboutService = new AboutService();

    const statusColors = {
        queued: 'info',
        running: 'warning',
        completed: 'success',
        skipped: 'default',
        failed: 'error',
    };

    export default {
        name: 'StorageManagementTab',
        data: () => ({
            storageSpaceMaxUsed: process.env.VUE_APP_STORAGE_SPACE_MAX_USED,
            storage: null,
            thinRequested: false,
            pollFailed: false,
            poller: null,
        }),
        computed: {
            storageSpaceUsed() {
                return Math.round((this.storage.space.used * 100) / this.storage.space.total);
            },
            storageRelativeCleanTime() {
                return moment(this.storage.thinning.last).fromNow();
            },
            storageCleanTime() {
                return moment(this.storage.thinning.last).format('LLL');
            },
            cleanupStatus() {
                return cleanupStatus(this.storage);
            },
            cleanupStatusVisible() {
                return this.cleanupStatus !== 'idle';
            },
            cleanupStatusColor() {
                return statusColors[this.cleanupStatus] || 'default';
            },
            cleanupBusy() {
                return this.thinRequested || isCleanupActive(this.storage);
            },
            cleanButtonTitle() {
                return this.$t(
                    !this.storage.screenshots_available || this.cleanupBusy
                        ? 'about.storage.thin_unavailable'
                        : 'about.storage.thin_available',
                );
            },
        },
        methods: {
            async cleanStorage() {
                if (this.cleanupBusy) {
                    return;
                }

                this.thinRequested = true;

                try {
                    const { status } = await aboutService.startCleanup();

                    if (status !== 204) {
                        throw new Error(`Unexpected status ${status}`);
                    }

                    // Accepted into the queue, not started: the worker reports the progress via GET about/storage
                    if (this.storage && !isCleanupActive(this.storage)) {
                        this.storage = {
                            ...this.storage,
                            thinning: { ...this.storage.thinning, status: 'queued', now: false },
                        };
                    }

                    this.$Message.info(this.$t('about.storage.messages.queued'));

                    if (this.poller) {
                        this.poller.setActive(true);
                        this.poller.refresh();
                    }
                } catch (e) {
                    this.$Message.error(this.$t('about.storage.messages.queue_failed'));
                } finally {
                    this.thinRequested = false;
                }
            },
            applyStorage(storage) {
                const finished = finishedCleanupStatus(this.storage, storage);

                this.storage = storage;
                this.pollFailed = false;

                if (finished === 'completed') {
                    this.$Message.success(this.$t('about.storage.messages.completed'));
                } else if (finished === 'skipped') {
                    this.$Message.info(this.$t('about.storage.messages.skipped'));
                } else if (finished === 'failed') {
                    this.$Message.error(this.$t('about.storage.messages.failed'));
                }
            },
            onPollError(error) {
                // Only a failed refresh of a loaded page is shown: the initial one falls back to "no storage"
                this.pollFailed = Boolean(this.storage);

                if (process.env.NODE_ENV === 'development') {
                    console.warn(error && error.response ? error.response : 'request to storage is canceled');
                }
            },
            getSize(value) {
                if (value < 1024) {
                    return `${value} B`;
                }

                const KB = value / 1024;

                if (KB < 1024) {
                    return `${Math.round(KB)} KB`;
                }

                const MB = KB / 1024;

                if (MB < 1024) {
                    return `${Math.round(MB)} MB`;
                }

                const GB = MB / 1024;

                if (GB < 1024) {
                    return `${Math.round(GB)} GB`;
                }

                return `${Math.round(GB / 1024)} TB`;
            },
        },
        mounted() {
            this.poller = createStoragePoller({
                fetch: () => aboutService.getStorageInfo(),
                onUpdate: storage => this.applyStorage(storage),
                onError: error => this.onPollError(error),
                isHidden: () => document.hidden,
            });

            this.poller.start();
        },
        beforeDestroy() {
            this.poller.stop();
            this.poller = null;
        },
    };
</script>

<style lang="scss" scoped>
    .storage {
        .at-progress {
            position: relative;
            top: 3px;
        }

        & > div {
            text-align: left;
        }

        .at-btn {
            margin-top: 15px;
        }

        &__warning {
            color: $color-error;
        }
    }
</style>
