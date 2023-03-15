<template>
    <div class="api-error">
        <div class="container">
            <h1 class="api-error__code">500</h1>
            <h1 class="api-error__title">Whoops!</h1>
            <p class="api-error__description">{{ $t('message.api_error') }}</p>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'ApiError',
    };
</script>

<style lang="scss">
    .api-error {
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
