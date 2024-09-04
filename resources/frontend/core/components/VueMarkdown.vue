<script>
    import MarkdownIt from 'markdown-it';

    export default {
        name: 'VueMarkdown',
        props: {
            source: {
                type: String,
                required: true,
            },
            options: {
                type: Object,
                required: false,
            },
            plugins: {
                type: Array,
                required: false,
            },
        },
        data() {
            const md = new MarkdownIt(this.options);
            for (const plugin of this.plugins ?? []) {
                md.use(plugin);
            }
            return {
                md,
            };
        },
        computed: {
            content() {
                return this.md.render(this.source);
            },
        },
        render(h) {
            return h('div', { domProps: { innerHTML: this.content } });
        },
    };
</script>
