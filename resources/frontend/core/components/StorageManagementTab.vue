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
            <p class="row">
                <at-button
                    :disabled="thinRequested || !storage.screenshots_available || storage.thinning.now"
                    :loading="thinRequested"
                    :title="cleanButtonTitle"
                    class="col-10"
                    hollow
                    @click="cleanStorage"
                >
                    {{ thinRequested ? '' : $t('about.storage.thin') }}
                </at-button>
            </p>
        </template>
        <p v-else v-t="'about.no_storage'" />
    </div>
</template>
<script>
    import moment from 'moment';
    import AboutService from '@/services/resource/about.service';

    const aboutService = new AboutService();

    export default {
        name: 'StorageManagementTab',
        data: () => ({
            storageSpaceMaxUsed: process.env.VUE_APP_STORAGE_SPACE_MAX_USED,
            storage: null,
            thinRequested: false,
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
            cleanButtonTitle() {
                return this.$t(
                    !this.storage.screenshots_available || this.storage.thinning.now
                        ? 'about.storage.thin_unavailable'
                        : 'about.storage.thin_available',
                );
            },
        },
        methods: {
            async cleanStorage() {
                this.thinRequested = true;

                try {
                    const { status } = await aboutService.startCleanup();

                    if (status === 204) {
                        this.$Message.success('Thin has been queued!');

                        this.storage.thinning.now = true;
                    } else {
                        this.$Message.error('Error happened during thin queueing!');
                    }
                } catch (e) {
                    this.$Message.error('Error happened during thin queueing!');
                } finally {
                    this.thinRequested = false;
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
        async mounted() {
            this.isLoading = true;
            try {
                this.storage = await aboutService.getStorageInfo();
            } catch ({ response }) {
                if (process.env.NODE_ENV === 'development') {
                    console.warn(response ? response : 'request to storage is canceled');
                }
            }

            this.isLoading = false;
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
    }
</style>
