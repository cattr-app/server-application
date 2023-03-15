<template>
    <div class="at-select" :class="{ 'at-select--visible': visible }">
        <v-select
            ref="select"
            v-model="model"
            class="timezone-select"
            :options="paginated"
            :filterable="false"
            :placeholder="$t('control.select')"
            @open="onOpen"
            @close="onClose"
            @search="search = $event"
        >
            <template #list-footer>
                <li v-show="hasNextPage" ref="load" class="vs__dropdown-option">Loading...</li>
            </template>
        </v-select>
        <i class="icon icon-chevron-down at-select__arrow" />
    </div>
</template>

<script>
    import moment from 'moment-timezone';
    import vSelect from 'vue-select';

    export default {
        props: {
            value: {
                type: [String, Object],
                required: true,
            },
        },
        components: {
            vSelect,
        },
        data() {
            return {
                timezones: [],
                limit: 10,
                search: '',
                observer: null,
                visible: false,
            };
        },
        computed: {
            model: {
                get() {
                    return {
                        value: this.value,
                        label: this.formatTimezone(this.value),
                    };
                },
                set(option) {
                    if (!option) return;

                    this.$emit('onTimezoneChange', option.value);
                },
            },
            filtered() {
                if (!this.timezones || !this.timezones.length) return [];

                return this.timezones.filter(timezone =>
                    timezone.label.toLowerCase().includes(this.search.toLowerCase()),
                );
            },
            paginated() {
                return this.filtered.slice(0, this.limit);
            },
            hasNextPage() {
                return this.paginated.length < this.filtered.length;
            },
        },
        methods: {
            inputHandler(value) {
                this.$emit('onTimezoneChange', value);
            },
            async onOpen() {
                if (this.hasNextPage) {
                    await this.$nextTick();
                    this.observer.observe(this.$refs.load);
                }
                this.visible = true;
            },
            onClose() {
                this.visible = false;
                this.observer.disconnect();
            },
            async infiniteScroll([{ isIntersecting, target }]) {
                if (isIntersecting) {
                    const ul = target.offsetParent;
                    const scrollTop = target.offsetParent.scrollTop;
                    this.limit += 10;
                    await this.$nextTick();
                    ul.scrollTop = scrollTop;
                }
            },
            setTimezones() {
                if (this.timezones.length > 1) return;

                moment.tz.names().map(timezoneName => {
                    if (this.timezones.some(t => t.value === timezoneName)) {
                        return;
                    }

                    //Asia/Kolkata
                    if (timezoneName === 'Asia/Calcutta') {
                        timezoneName = 'Asia/Kolkata';
                    }

                    if (typeof timezoneName !== 'string') return;

                    this.timezones.push({
                        value: timezoneName,
                        label: this.formatTimezone(timezoneName),
                    });
                });
            },
            formatTimezone(timezone) {
                return `${timezone} (GMT${moment.tz(timezone).format('Z')})`;
            },
        },
        created() {
            this.timezones.push({
                value: this.value,
                label: this.formatTimezone(this.value),
            });
            this.setTimezones();
        },
        mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll);
        },
    };
</script>

<style lang="scss" scoped>
    .timezone-select {
        min-width: 240px;

        &::v-deep {
            .vs__dropdown-menu {
                width: auto;
                min-width: 100%;
            }
        }
    }
</style>
