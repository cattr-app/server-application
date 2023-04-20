<template>
    <at-menu class="navbar container-fluid" router mode="horizontal">
        <router-link to="/" class="navbar__logo"></router-link>
        <div v-if="loggedIn">
            <template v-for="(item, key) in navItems">
                <navigation-menu-item :key="key" :to="item.to || undefined" @click="item.click || undefined">
                    {{ $t(item.label) }}
                </navigation-menu-item>
            </template>
            <template v-for="(item, key) in navDropdowns">
                <at-submenu :key="key" :title="$t(key)">
                    <template slot="title">{{ $t(key) }}</template>
                    <template v-for="(val, itemKey) in item">
                        <navigation-menu-item :key="itemKey" :to="val.to || undefined" @click="val.click || undefined">
                            {{ $t(val.label) }}
                        </navigation-menu-item>
                    </template>
                </at-submenu>
            </template>
        </div>
        <at-dropdown v-if="loggedIn" placement="bottom-right" @on-dropdown-command="userDropdownHandle">
            <i class="icon icon-chevron-down at-menu__submenu-icon"></i>
            <user-avatar :border-radius="10" :user="user"></user-avatar>
            <at-dropdown-menu slot="menu">
                <template v-for="(item, key) of userDropdownItems">
                    <at-dropdown-item :key="key" :name="item.to.name">
                        <span><i class="icon" :class="[item.icon]"></i>{{ item.title }}</span>
                    </at-dropdown-item>
                </template>
                <li class="at-dropdown-menu__item" @click="logout()">
                    <i class="icon icon-log-out"></i> {{ $t('navigation.logout') }}
                </li>
            </at-dropdown-menu>
        </at-dropdown>
    </at-menu>
</template>

<script>
    import NavigationMenuItem from '@/components/NavigationMenuItem';
    import UserAvatar from '@/components/UserAvatar';
    import { getModuleList } from '@/moduleLoader';
    import { mapGetters } from 'vuex';

    export default {
        components: {
            UserAvatar,
            NavigationMenuItem,
        },
        data() {
            return {
                modules: Object.values(getModuleList()).map(i => i.moduleInstance),
            };
        },
        methods: {
            userDropdownHandle(route) {
                this.$router.push({ name: route });
            },
            async logout() {
                await this.$store.getters['user/apiService'].logout();
            },
        },
        computed: {
            navItems() {
                const navItems = [];
                this.modules.forEach(m => {
                    const entries = m.getNavbarEntries();
                    entries.forEach(e => {
                        if (e.displayCondition(this.$store)) {
                            navItems.push(e.getData());
                        }
                    });
                });

                return navItems;
            },
            navDropdowns() {
                const entriesDr = {};
                this.modules.forEach(m => {
                    const entriesDropdown = m.getNavbarEntriesDropdown();
                    Object.keys(entriesDropdown).forEach(key => {
                        const isAllowItem = entriesDropdown[key][0].displayCondition(this.$store);
                        if (
                            !Object.prototype.hasOwnProperty.call(entriesDr, entriesDropdown[key][0].section) &&
                            isAllowItem
                        ) {
                            entriesDr[entriesDropdown[key][0].section] = [];
                        }
                        if (isAllowItem) {
                            entriesDr[entriesDropdown[key][0].section].push(entriesDropdown[key][0]);
                        }
                    });
                });

                return entriesDr;
            },
            ...mapGetters('user', ['user']),
            userDropdownItems() {
                const items = [
                    {
                        name: 'about',
                        to: {
                            name: 'about',
                        },
                        title: this.$t('navigation.about'),
                        icon: 'icon-info',
                    },
                    // {
                    //     name: 'desktop-login',
                    //     to: {
                    //         name: 'desktop-login',
                    //     },
                    //     title: this.$t('navigation.client-login'),
                    //     icon: 'icon-log-in',
                    // },
                ];
                this.modules.forEach(m => {
                    const entriesDropdown = m.getNavbarMenuEntriesDropDown();
                    Object.keys(entriesDropdown).forEach(el => {
                        const { displayCondition, label, to, click, icon } = entriesDropdown[el];
                        if (displayCondition(this.$store)) {
                            items.push({
                                to,
                                icon,
                                click,
                                title: this.$t(label),
                            });
                        }
                    });
                });

                return items;
            },
            rules() {
                return this.$store.getters['user/allowedRules'];
            },
            loggedIn() {
                return this.$store.getters['user/loggedIn'];
            },
        },
    };
</script>

<style lang="scss" scoped>
    .navbar {
        border-bottom: 0;
        box-shadow: 0px 0px 10px rgba(63, 51, 86, 0.1);
        display: flex;
        height: auto;
        justify-content: space-between;
        padding: 0.75em 24px;

        &__logo {
            background: url('../assets/logo.svg');
            background-size: cover;
            height: 45px;
            width: 45px;
        }

        &::v-deep {
            .at-menu {
                &__item-link {
                    &::after {
                        bottom: -0.75em;
                        height: 3px;
                    }
                }
            }

            .at-menu__submenu-title {
                padding-right: 0 !important;
            }

            .at-dropdown {
                align-items: center;
                display: flex;

                &-menu {
                    overflow: hidden;

                    &__item {
                        color: $gray-3;
                        font-weight: 600;

                        &:hover {
                            background-color: #fff;
                            color: $blue-2;
                        }
                    }
                }

                &__trigger {
                    align-items: center;
                    cursor: pointer;
                    display: flex;

                    .icon {
                        margin-right: 8px;
                    }
                }

                &__popover {
                    width: fit-content;
                }

                .at-dropdown-menu__item .icon {
                    margin-right: 6px;
                }
            }
        }
    }
</style>
