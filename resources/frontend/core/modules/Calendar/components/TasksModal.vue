<template>
    <div v-if="visible" class="tasks-modal">
        <div class="tasks-modal__header">
            <h4 class="tasks-modal__title">{{ $t('calendar.tasks', { date: formatDate(date) }) }}</h4>
            <at-button @click="close"><i class="icon icon-x"></i></at-button>
        </div>

        <ul class="tasks-modal__list">
            <li v-for="task of tasks" :key="task.id" class="tasks-modal__item">
                <a class="tasks-modal__link" target="_blank" :href="`/tasks/view/${task.id}`">
                    {{ task.task_name }}
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
    import moment from 'moment';

    export default {
        props: {
            date: {
                type: String,
                required: true,
            },
            tasks: {
                type: Array,
                required: true,
            },
        },
        computed: {
            visible() {
                return this.tasks.length > 0;
            },
        },
        methods: {
            close() {
                this.$emit('close');
            },
            formatDate(date) {
                return moment(date).format('LL');
            },
        },
    };
</script>

<style lang="scss" scoped>
    .tasks-modal {
        background: #fff;

        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

        padding: 0.75em 24px;

        z-index: 10;

        &__header {
            display: flex;
            flex-flow: row nowrap;
            align-items: center;
        }

        &__title {
            flex: 1;
            text-align: center;
        }
    }
</style>
