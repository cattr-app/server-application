<template>
    <div class="colors">
        <ul v-if="colorsConfig.length > 0">
            <li v-for="(config, index) in colorsConfig" :key="index" class="color-readiness__item">
                <at-input
                    :value="getPercent(config)"
                    class="color-readiness__start"
                    type="number"
                    placeholder="Start percent"
                    :min="0"
                    :max="100"
                    @blur="setStart(index, $event)"
                >
                    <template slot="append">
                        <span v-if="config.start < 1">%</span>
                        <span v-else>{{ $t('Over Time') }}</span>
                    </template>
                </at-input>

                <at-input
                    v-if="config.start < 1"
                    :value="parseInt(config.end * 100)"
                    class="color-readiness__end"
                    type="number"
                    placeholder="End percent"
                    :min="1"
                    :max="100"
                    @blur="setEnd(index, $event)"
                >
                    <template slot="append">
                        <span>%</span>
                    </template>
                </at-input>
                <div class="color-readiness__color">
                    <ColorInput
                        class="color-input at-input__original"
                        :value="config.color"
                        @change="setColor(config, $event)"
                    />
                </div>
                <at-button class="color-readiness__remove" @click.prevent="remove(index)">
                    <span class="icon icon-x"></span>
                </at-button>
            </li>
        </ul>
        <at-button-group>
            <at-button class="color-readiness__add" :disabled="isDisabledAddButton" @click.prevent="add">{{
                $t('control.add')
            }}</at-button>
            <at-button class="color-readiness__reset" @click.prevent="reset">{{ $t('control.reset') }}</at-button>
        </at-button-group>
    </div>
</template>

<script>
    import ColorInput from '@/components/ColorInput';

    const defaultInterval = { color: '#3ba8da', start: 0, end: 0.1 };

    export default {
        props: {
            colorsConfig: {
                required: true,
            },
        },
        components: {
            ColorInput,
        },
        data() {
            return {};
        },
        computed: {
            usedIntervals() {
                return this.colorsConfig.map(el => ({
                    start: parseInt(el.start * 100),
                    end: parseInt(el.end * 100),
                }));
            },
            freeIntervals() {
                if (!this.usedIntervals.length) {
                    return [{ start: 0, end: 100 }];
                }

                const lastIndex = this.usedIntervals.length - 1;
                return this.usedIntervals.reduce(
                    (accum, curEl, i, arr) => {
                        const index = i === arr.length - 1 ? i : i + 1;

                        //if have OverTime
                        if (lastIndex === index && curEl.start === 100 && curEl.end === 0) {
                            return accum;
                        }

                        //if the first interval doesn't start from null
                        if (i === 0 && curEl.start !== 0) {
                            accum[i].end = curEl.start - 1;
                        }

                        //if first interval starts from null, then remove the default intterval
                        if (i === 0 && accum[i].end === 0) {
                            accum.splice(i, 1);
                        }

                        //if not have last free interval
                        if (lastIndex === i && curEl.end !== 100) {
                            return [...accum, { start: curEl.end + 1, end: 100 }];
                        }

                        //if there's no Overtime, we can add it
                        if (lastIndex === i && curEl.start !== 100) {
                            return [...accum, { start: 100, end: '' }];
                        }

                        // if the interval is 100
                        if (arr[index].start === curEl.end) {
                            return accum;
                        }

                        // if there's free interval
                        if (arr[index].start - 1 !== curEl.end) {
                            return [...accum, { start: curEl.end + 1, end: arr[index].start - 1 }];
                        }

                        return accum;
                    },
                    [{ start: 0, end: 0 }],
                );
            },
            isDisabledAddButton() {
                return this.freeIntervals.length === 0;
            },
        },
        methods: {
            add() {
                let interval = defaultInterval;

                if (this.freeIntervals.length > 0) {
                    interval = {
                        start: this.freeIntervals[0].start / 100,
                        end: this.freeIntervals[0].end / 100,
                        color: '#3ba8da',
                    };
                }

                this.$emit('addColorReadiness', [interval]);
            },

            remove(index) {
                this.$emit('onRemoveRelation', index);
            },
            setEnd(index, ev) {
                const newEnd = ev.target.valueAsNumber;
                if (this.colorsConfig[index].end === newEnd / 100) {
                    return;
                }

                const haveThisInterval = this.usedIntervals.filter((el, i) => {
                    if (index !== i) {
                        return newEnd >= el.start && newEnd <= el.end;
                    }
                });

                if (haveThisInterval.length > 0) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('message.error'),
                        message: this.$t('settings.color_interval.notification.interval_already_in_use'),
                    });

                    this.$forceUpdate();

                    return;
                }

                this.$emit('setEnd', index, newEnd / 100);
            },
            setStart(index, ev) {
                const newStart = ev.target.valueAsNumber;
                if (this.colorsConfig[index].start === newStart / 100) {
                    return;
                }

                if (newStart >= 100) {
                    const haveOverTime = this.colorsConfig.filter(el => el.start === 1).length;
                    if (haveOverTime > 0) {
                        this.$Notify({
                            type: 'error',
                            title: this.$t('message.error'),
                            message: this.$t('settings.color_interval.notification.gt_100'),
                        });

                        this.$forceUpdate();

                        return;
                    }
                }

                const haveThisInterval = this.usedIntervals.filter((el, i) => {
                    if (index !== i) {
                        return newStart >= el.start && newStart <= el.end;
                    }
                });

                if (haveThisInterval.length > 0) {
                    this.$Notify({
                        type: 'error',
                        title: this.$t('message.error'),
                        message: this.$t('settings.color_interval.notification.interval_already_in_use'),
                    });

                    this.$forceUpdate();

                    return;
                }

                this.$emit('setStart', index, newStart / 100);

                if (newStart === 100) {
                    this.$emit('setEnd', index, 0);
                } else if (this.colorsConfig[index].end === 0) {
                    //eslint-disable-next-line vue/no-mutating-props
                    this.colorsConfig[index].end = 1;
                }
            },
            getPercent(config) {
                if (config.start >= 1) {
                    config.isOverTime = true;
                    this.$emit('setOverTime', this.colorsConfig);
                }
                if (config.start < 1 && 'isOverTime' in config) {
                    this.$delete(config, 'isOverTime');
                    this.$emit('setOverTime', this.colorsConfig);
                }

                return parseInt(config.start * 100);
            },
            setColor(config, event) {
                this.$set(config, 'color', event);
            },
            reset() {
                this.$emit('reset');
            },
        },
    };
</script>

<style lang="scss" scoped>
    .color-input {
        width: 170px;
        height: 40px;
        cursor: pointer;
        border-radius: 5px;
        padding: 0px;
        border: none;
    }
    .color-readiness {
        &__item {
            display: flex;
            flex-flow: row nowrap;
        }
        &__start,
        &__end,
        &__color {
            flex: 1;
            margin-right: 0.5em;
            margin-bottom: 0.75em;
        }
        &__remove {
            height: 40px;
        }
        &__color {
            max-width: 170px;
        }
    }
    input[type='color' i]::-webkit-color-swatch-wrapper,
    input[type='color' i]::-webkit-color-swatch {
        padding: 0px;
        border: none;
    }
</style>
