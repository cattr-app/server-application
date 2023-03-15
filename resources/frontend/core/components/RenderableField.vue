<script>
    import { mapGetters } from 'vuex';
    export default {
        name: 'RenderableField',
        props: {
            render: {
                required: true,
                type: Function,
            },
            value: {
                default: Object,
            },
            field: {
                required: true,
                type: Object,
            },
            values: {
                type: Object,
            },
            setValue: {
                type: Function,
            },
        },
        data() {
            return {
                currentValue: this.value,
            };
        },
        watch: {
            value(val) {
                this.currentValue = val;
            },
        },
        computed: {
            ...mapGetters('user', ['companyData']),
        },
        methods: {
            inputHandler(val) {
                this.$emit('input', val);
                this.$emit('change', val);
            },
            focusHandler(evt) {
                this.$emit('focus', evt);
            },
            blurHandler(evt) {
                this.$emit('blur', evt);
            },
        },
        render(h) {
            return this.render(h, {
                inputHandler: this.inputHandler,
                currentValue: this.currentValue,
                focusHandler: this.focusHandler,
                blurHandler: this.blurHandler,
                field: this.field,
                values: this.values,
                setValue: this.setValue,
                companyData: this.companyData,
            });
        },
    };
</script>
