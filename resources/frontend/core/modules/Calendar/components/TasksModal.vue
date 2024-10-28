<template>
    <div v-if="visible" class="tasks-modal">
        <div class="tasks-modal__header">
            <h4 class="tasks-modal__title">{{ $t('calendar.tasks', { date: formatDate(date) }) }}</h4>
            <at-button @click="close"><i class="icon icon-x"></i></at-button>
        </div>

        <ul class="tasks-modal__list">
            <li v-for="task of tasks" :key="task.id" class="tasks-modal__item">
                <a class="tasks-modal__link" :href="`/tasks/view/${task.id}`">
                    {{ task.task_name }}
                </a>
            </li>
        </ul>
    </div>
</template>

<script>
    import moment from 'moment';

    const MODAL_VISIBLE_CLASS = 'modal-visible';

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
        watch: {
            visible(value) {
                if (value) {
                    document.body.classList.add(MODAL_VISIBLE_CLASS);
                } else {
                    document.body.classList.remove(MODAL_VISIBLE_CLASS);
                }
            },
        },
        beforeDestroy() {
            document.body.classList.remove(MODAL_VISIBLE_CLASS);
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

        overflow: auto;

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

<style lang="scss">
    body.modal-visible {
        overflow: hidden;
    }
</style>
