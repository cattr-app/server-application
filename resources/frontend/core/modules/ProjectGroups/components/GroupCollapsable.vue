<template>
    <div class="groups">
        <at-collapse simple accordion @on-change="changeHandler">
            <at-collapse-item
                v-for="(group, index) in groups"
                :key="index"
                :disabled="group.projects_count === 0"
                :class="{
                    groups__disabled: group.projects_count === 0,
                }"
                :name="String(index)"
            >
                <div slot="title">
                    <div class="groups__header">
                        <h5 class="groups__title">
                            <span v-if="group.depth > 0" class="groups__depth">{{ getSpaceByDepth(group.depth) }}</span
                            >{{ group.name }} ({{ group.projects_count }})
                        </h5>
                    </div>
                </div>
                <div v-if="group.projects_count > 0 && isOpen(index)" class="groups__projects-wrapper">
                    <GroupProjects :group-id="group.id" class="groups__projects" />
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
            getSpaceByDepth: function (depth) {
                return ''.padStart(depth, '-');
            },
            changeHandler(event) {
                this.opened = event;
            },
            isOpen(id) {
                return this.opened[0] === String(id);
            },
            calculateProjectsCount(group) {
                let count = group.projects_count;
                group.children.forEach(child => {
                    count += this.calculateProjectsCount(child);
                });
                return count;
            },
        },
    };
</script>

<style lang="scss" scoped>
    .groups {
        &__header {
            //display: flex;
            //justify-content: space-between;
            //align-items: center;
            //border-bottom: none;
            //padding: 14px 21px;
            //border-bottom: 3px solid $blue-3;
        }

        &__title {
            //color: $black-900;
            //font-size: 1rem;
            //font-weight: bold;
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
