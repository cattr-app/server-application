<template>
    <div class="at-select">
        <v-select
            :options="options"
            label="label"
            :placeholder="$t('time_intervals.task_select.placeholder')"
            :clearable="false"
            @input="inputHandler($event.id)"
            @search="onSearch"
        >
            <div slot="no-options">{{ $t('time_intervals.task_select.no_options') }}</div>
        </v-select>
        <i class="icon icon-chevron-down at-select__arrow" />
    </div>
</template>

<script>
    import vSelect from 'vue-select';

    export default {
        name: 'LazySelect',
        components: {
            vSelect,
        },
        props: {
            value: {
                type: Number,
            },
            userID: {
                type: Number,
            },
            service: {
                type: Object,
                required: true,
            },
            inputHandler: {
                type: Function,
                required: true,
            },
        },
        data() {
            return {
                options: [],
            };
        },
        methods: {
            onSearch(query, loading) {
                if (query.length >= 3) {
                    this.fetchTasks(query, loading);
                } else {
                    this.options = [];
                }
            },
            async fetchTasks(query, loading) {
                loading(true);

                const filters = { search: { query, fields: ['task_name'] }, with: ['project'] };
                if (this.userID) {
                    filters['where'] = { 'users.id': this.userID };
                }

                this.options = await this.service.getWithFilters(filters).then(({ data }) => {
                    loading(false);

                    return data.data.map(task => {
                        const label =
                            typeof task.project !== 'undefined'
                                ? `${task.task_name} (${task.project.name})`
                                : task.task_name;

                        return { ...task, label };
                    });
                });
            },
        },
    };
</script>
