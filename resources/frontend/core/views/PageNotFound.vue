<template>
    <div class="error-page-system">
        <h1 class="code">404</h1>
        <h1 class="title">Whoops!</h1>
        <p class="description">{{ $t('message.page_not_found') }}</p>
    </div>
</template>

<script>
    export default {
        name: 'PageNotFound',
    };
</script>

<style lang="scss">
    .error-page-system {
        margin-left: 15%;
        margin-right: 15%;

        .code {
            text-align: center;
            color: $red-1;
            font-size: 10rem;
            line-height: 1;
        }

        .title {
            font-size: 3.5rem;
            text-align: center;
            margin-bottom: 1rem;
        }

        .description {
            font-size: 1.5rem;
            text-align: center;
            font-weight: bold;
        }
    }
</style>
