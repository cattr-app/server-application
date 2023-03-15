<template>
    <div class="dashboard">
        <div class="dashboard__routes">
            <h1 class="dashboard__link">
                <router-link :to="{ name: 'dashboard.timeline' }">{{ $t('dashboard.timeline') }}</router-link>
            </h1>
            <h1 v-if="$can('viewTeamTab', 'dashboard')" class="dashboard__link">
                <router-link :to="{ name: 'dashboard.team' }">{{ $t('dashboard.team') }}</router-link>
            </h1>
        </div>
        <div class="dashboard__content-wrapper">
            <router-view :key="$route.fullPath" />
        </div>
    </div>
</template>

<script>
    export default {
        name: 'Index',
    };
</script>

<style lang="scss" scoped>
    .dashboard {
        &__routes {
            margin-bottom: 1em;
            display: flex;
        }

        &__link {
            margin-right: $layout-03;
            font-size: 1.8rem;

            &:last-child {
                margin-right: initial;
            }

            a {
                color: #b1b1be;
            }

            .router-link-active {
                color: #2e2ef9;
            }
        }
    }
</style>
