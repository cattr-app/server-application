<template>
    <div class="history">
        <div v-for="change in task.changes" :key="change.id" class="history-change">
            <TeamAvatars class="history-change-avatar" :users="[change.user]" />

            <template v-if="change.field === 'users'">
                {{
                    $t('projects.task_change_users', {
                        user: change.user.full_name,
                        date: fromNow(change.created_at),
                        value:
                            change.new_value && change.new_value.length
                                ? JSON.parse(change.new_value)
                                      .map(user => user.full_name)
                                      .join(', ')
                                : '',
                    })
                }}
            </template>

            <template v-else-if="change.field === 'status_id'">
                {{
                    $t('projects.task_change_to', {
                        user: change.user.full_name,
                        field: $t(`field.${change.field}`).toLocaleLowerCase(),
                        value: getStatusName(change.new_value),
                        date: fromNow(change.created_at),
                    })
                }}
            </template>

            <template v-else-if="change.field === 'priority_id'">
                {{
                    $t('projects.task_change_to', {
                        user: change.user.full_name,
                        field: $t(`field.${change.field}`).toLocaleLowerCase(),
                        value: getPriorityName(change.new_value),
                        date: fromNow(change.created_at),
                    })
                }}
            </template>

            <template v-else-if="change.field !== 'relative_position'">
                {{
                    $t('projects.task_change', {
                        user: change.user.full_name,
                        field: $t(`field.${change.field}`).toLocaleLowerCase(),
                        date: fromNow(change.created_at),
                    })
                }}
            </template>
        </div>
    </div>
</template>

<script>
    import TeamAvatars from '@/components/TeamAvatars';
    import StatusService from '@/services/resource/status.service';
    import PriorityService from '@/services/resource/priority.service';
    import { fromNow } from '@/utils/time';

    export default {
        components: {
            TeamAvatars,
        },
        props: {
            task: {
                type: Object,
                required: true,
            },
        },
        data() {
            return {
                statusService: new StatusService(),
                priorityService: new PriorityService(),
                statuses: [],
                priorities: [],
            };
        },
        async mounted() {
            this.statuses = await this.statusService.getAll();
            this.priorities = await this.priorityService.getAll();
        },
        methods: {
            fromNow,
            getStatusName(id) {
                const status = this.statuses.find(status => +status.id === +id);
                if (status) {
                    return status.name;
                }

                return '';
            },
            getPriorityName(id) {
                const priority = this.priorities.find(priority => +priority.id === +id);
                if (priority) {
                    return priority.name;
                }

                return '';
            },
        },
    };
</script>

<style lang="scss" scoped>
    .history {
        &-change {
            margin-top: 16px;
        }

        &-change-avatar {
            display: inline-block;
        }
    }
</style>
