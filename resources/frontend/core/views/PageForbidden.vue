<template>
    <div class="page-forbidden">
        <div class="container">
            <h1 class="page-forbidden__code">403</h1>
            <h1 class="page-forbidden__title">Whoops!</h1>
            <p class="page-forbidden__description">{{ $t('message.page_forbidden') }}</p>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'PageForbidden',
    };
</script>

<style lang="scss">
    .page-forbidden {
        text-align: center;

        &__code {
            color: $red-1;
            font-size: 8rem;
            line-height: 1;
            margin-bottom: 1rem;
        }

        &__title {
            font-size: 3rem;
            margin-bottom: 1rem;
            line-height: 1;
        }

        &__description {
            font-size: 1.5rem;
            font-weight: bold;
        }
    }
</style>
