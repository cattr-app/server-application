<template>
    <div class="drag-container">
        <ul class="drag-list">
            <li v-for="stage in stages" :key="stage" class="drag-column" :class="{ ['drag-column-' + stage]: true }">
                <span class="drag-column-header">
                    <slot :name="stage">
                        <h2>{{ stage }}</h2>
                    </slot>
                </span>
                <div class="drag-options"></div>
                <ul ref="list" class="drag-inner-list" :data-status="stage">
                    <li
                        v-for="block in getBlocks(stage)"
                        :key="block[idProp]"
                        class="drag-item"
                        :data-block-id="block[idProp]"
                    >
                        <slot :name="block[idProp]">
                            <strong>{{ block[statusProp] }}</strong>
                            <div>{{ block[idProp] }}</div>
                        </slot>
                    </li>
                </ul>
                <div class="drag-column-footer">
                    <slot :name="`footer-${stage}`"></slot>
                </div>
            </li>
        </ul>
    </div>
</template>

<script>
    import dragula from 'dragula';

    // Kanban board on dragula with the markup, props and events of vue-kanban 1.8 (without its state machine)
    export default {
        name: 'KanbanBoard',
        props: {
            // Column names; a column's header is the slot with the stage name
            stages: {
                type: Array,
                required: true,
            },
            // Cards; a card's content is the slot with the block id
            blocks: {
                type: Array,
                required: true,
            },
            // dragula options
            config: {
                type: Object,
                default: () => ({}),
            },
            idProp: {
                type: String,
                default: 'id',
            },
            statusProp: {
                type: String,
                default: 'status',
            },
        },
        methods: {
            getBlocks(status) {
                return this.blocks.filter(block => block[this.statusProp] === status);
            },
            accepts() {
                return true;
            },
            allowedTargets(el, source) {
                const block = this.blocks.find(b => b[this.idProp] === el.dataset.blockId);
                return this.drake.containers.filter(c => this.options.accepts(block, c, source));
            },
            forbiddenTargets(el, source) {
                const allowed = this.allowedTargets(el, source);
                return this.drake.containers.filter(c => !allowed.includes(c));
            },
        },
        mounted() {
            this.options = {
                ...this.config,
                accepts: this.config.accepts || this.accepts,
                mirrorContainer: this.$el,
            };
            this.drake = dragula(this.$refs.list, this.options)
                .on('drag', (el, source) => {
                    this.$emit('drag', el, source);
                    el.classList.add('is-moving');
                    this.allowedTargets(el, source).forEach(c => c.classList.add('allowed'));
                    this.forbiddenTargets(el, source).forEach(c => c.classList.add('forbidden'));
                })
                .on('dragend', el => {
                    this.$emit('dragend', el);
                    el.classList.remove('is-moving');
                    this.drake.containers.forEach(c => c.classList.remove('allowed', 'forbidden'));
                    window.setTimeout(() => {
                        el.classList.add('is-moved');
                        window.setTimeout(() => {
                            el.classList.remove('is-moved');
                        }, 600);
                    }, 100);
                })
                .on('drop', (block, list, source, sibling) => {
                    this.$emit('drop', block, list, source, sibling);
                    let index = 0;
                    for (index = 0; index < list.children.length; index += 1) {
                        if (list.children[index].classList.contains('is-moving')) {
                            break;
                        }
                    }

                    this.$emit('update-block', block.dataset.blockId, list.dataset.status, index);
                })
                .on('cancel', (el, container, source) => this.$emit('cancel', el, container, source))
                .on('remove', (el, container, source) => this.$emit('remove', el, container, source))
                .on('shadow', (el, container, source) => this.$emit('shadow', el, container, source))
                .on('over', (el, container, source) => this.$emit('over', el, container, source))
                .on('out', (el, container, source) => this.$emit('out', el, container, source))
                .on('cloned', (clone, original, type) => this.$emit('cloned', clone, original, type));
            this.$emit('init', this.drake);
        },
        updated() {
            this.drake.containers = this.$refs.list || [];
        },
        beforeDestroy() {
            // dragula listens on the document; release it together with the board
            this.drake.destroy();
        },
    };
</script>
