<template>
    <div :class="containerClass">
        <h1 class="page-title">{{ $t('navigation.company_settings') }}</h1>
        <div class="at-container settings">
            <div class="row">
                <div class="col-5">
                    <at-menu v-if="sections" class="settings__menu" router mode="vertical">
                        <template v-for="(section, key) in sections">
                            <at-menu-item v-if="section.access" :key="key" :to="{ name: section.pathName }">
                                {{ $t(section.label) }}
                            </at-menu-item>
                        </template>
                    </at-menu>
                </div>
                <div class="col-19">
                    <div class="settings__content">
                        <router-view />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        name: 'CompanySettings',
        computed: {
            containerClass() {
                return 'container';
            },
            sections() {
                return this.$store.getters['settings/sections']
                    .filter(section => section.scope === 'company')
                    .sort((a, b) => a.order - b.order);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .settings {
        &::v-deep {
            .page-title {
                font-size: 24px;
            }
        }

        &__menu {
            padding: $layout-01 0;
            height: 100%;
            border-top-left-radius: $border-radius-lger;
            border-bottom-left-radius: $border-radius-lger;
        }

        &__content {
            padding: $spacing-05 $spacing-06 $spacing-07;
        }
    }

    .settings__content::v-deep {
        .at-container,
        .at-container__inner,
        .crud {
            all: unset;

            &__table {
                margin-bottom: $layout-01;
            }
        }
    }
</style>
