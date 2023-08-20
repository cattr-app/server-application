<template>
    <div>
        <h3>{{ $t('universal-report.project_information') }}</h3>
        <div v-for="(value, field) in project" :key="field" class="data-entry">
            <div class="row">
                <div class="col-6 label">{{ $t(`field.${field}`) }}:</div>
                <div class="col">
                    <Skeleton>
                        <span>{{ value }}</span>
                    </Skeleton>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import { Skeleton } from 'vue-loading-skeleton';

    export default {
        props: {
            project: {
                type: Object,
                required: true,
            },
        },
        components: {
            Skeleton,
        },
    };
</script>
