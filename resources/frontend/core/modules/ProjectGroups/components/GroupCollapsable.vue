<template>
    <div class="groups">
        <at-collapse simple accordion @on-change="event => (opened = event)">
            <at-collapse-item v-for="(group, index) in groups" :key="index" :disabled="group.projects_count === 0">
                <div slot="title">
                    <div class="groups__header">
                        <h5 :class="{ groups__disabled: group.projects_count === 0 }" class="groups__title">
                            <span
                                v-if="group.depth > 0"
                                :class="{ groups__disabled: group.projects_count === 0 }"
                                class="groups__depth"
                            >
                                {{ group.depth | getSpaceByDepth }}
                            </span>
                            <span v-if="group.breadCrumbs">
                                <span
                                    v-for="(breadCrumb, index) in group.breadCrumbs"
                                    :key="index"
                                    @click.stop="$emit('getTargetClickGroupAndChildren', breadCrumb.id)"
                                >
                                    {{ breadCrumb.name }} {{ group.breadCrumbs.length - 1 > index ? '/' : '' }}
                                </span>
                                <span>({{ group.projects_count }})</span>
                            </span>
                            <span v-else>{{ group.name + ' (' + group.projects_count + ')' }}</span>
                        </h5>
                        <router-link
                            class="groups__title__link"
                            :to="`/project-groups/edit/${group.id}`"
                            target="_blank"
                            @click.stop
                        >
                            <i class="icon icon-external-link" />
                        </router-link>
                    </div>
                </div>
                <div v-if="group.projects_count > 0 && isOpen(index)" class="groups__projects-wrapper">
                    <GroupProjects :group-id="group.id" class="groups__projects" @reloadData="$emit('reloadData')" />
                </div>
            </at-collapse-item>
        </at-collapse>
    </div>
</template>

<script>
    import GroupProjects from '../components/GroupProjects';

    export default {
        name: 'GroupCollapsable',
        components: { GroupProjects },
        data() {
            return {
                opened: [],
            };
        },
        computed: {
            projectsCount() {
                return `(${this.group.projects_count})`;
            },
        },
        props: {
            groups: {
                type: Array,
                required: true,
            },
        },
        methods: {
            isOpen(index) {
                return this.opened[0] === String(index);
            },
            calculateProjectsCount(group) {
                let count = group.projects_count;
                group.children.forEach(child => {
                    count += this.calculateProjectsCount(child);
                });
                return count;
            },
            reloadData() {
                this.$emit('reloadData');
            },
        },
        filters: {
            getSpaceByDepth(value) {
                return ''.padStart(value, '-');
            },
        },
    };
</script>

<style lang="scss" scoped>
    .groups {
        &__title {
            display: inline-block;
        }

        .icon-external-link {
            font-size: 20px;
        }

        &__disabled {
            opacity: 0.3;
        }

        &__depth {
            padding-right: 0.3em;
            letter-spacing: 0.1em;
            opacity: 0.3;
            font-weight: 300;
        }
        &__header {
            display: flex;
        }
        &::v-deep {
            .at-collapse {
                &__item--active {
                    background-color: #fff;
                    .groups__title {
                        color: $blue-2;
                    }
                }
                &__header {
                    display: flex;
                    align-items: center;
                    padding: 15px;
                }

                &__content {
                    padding: 10px;
                }

                &__icon.icon-chevron-right {
                    position: static;
                    display: block;
                    color: black;
                    margin-right: 10px;
                }
            }
        }
    }
</style>
