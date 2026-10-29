<script>
    // Loading placeholder with the API and look of vue-loading-skeleton 1.1.9 (PuSkeleton).
    const BACKGROUND = '#eeeeee';
    const HIGHLIGHT = '#f5f5f5';

    const toSize = value => (typeof value === 'number' ? `${value}px` : value);

    // The default slot counts as empty when its first node is whitespace-only text
    const isEmptySlot = children => {
        if (!children) {
            return true;
        }

        const [firstNode] = children;
        const text = firstNode.text ? firstNode.text.replace(/\s/g, '') : firstNode.text;

        return typeof firstNode.tag === 'undefined' && !text;
    };

    export default {
        name: 'Skeleton',
        props: {
            // Not set: the skeleton is shown while the default slot is empty
            loading: {
                type: Boolean,
                default: undefined,
            },
            count: {
                type: Number,
                default: 1,
            },
            // Seconds of one highlight pass; 0 disables the animation
            duration: {
                type: Number,
                default: 1.5,
            },
            width: [String, Number],
            height: [String, Number],
            circle: Boolean,
            tag: {
                type: String,
                default: 'span',
            },
        },
        render(h) {
            const showLoading = typeof this.loading !== 'undefined' ? this.loading : isEmptySlot(this.$slots.default);

            if (!showLoading) {
                return this.tag ? h(this.tag, this.$slots.default) : this.$slots.default;
            }

            const style = {
                backgroundColor: BACKGROUND,
                backgroundImage: this.duration
                    ? `linear-gradient(90deg, ${BACKGROUND}, ${HIGHLIGHT}, ${BACKGROUND})`
                    : '',
            };
            if (this.duration) {
                style.animation = `SkeletonLoading ${this.duration}s ease-in-out infinite`;
            }
            if (this.width) {
                style.width = toSize(this.width);
            }
            if (this.height) {
                style.height = toSize(this.height);
            }
            if (this.circle) {
                style.borderRadius = '50%';
            }

            const elements = [];
            for (let i = 0; i < this.count; i += 1) {
                elements.push(h('span', { key: i, class: 'pu-skeleton', style }, ['‌']));
            }

            return h(this.tag || 'span', elements);
        },
    };
</script>

<style>
    .pu-skeleton {
        background-size: 200px 100%;
        background-repeat: no-repeat;
        border-radius: 3px;
        display: inline-block;
        line-height: 1;
        width: 100%;
        height: inherit;
    }

    @keyframes SkeletonLoading {
        0% {
            background-position: -200px 0;
        }
        100% {
            background-position: calc(200px + 100%) 0;
        }
    }
</style>
