<template>
    <div class="duration-input">
        <at-button-group class="duration-input__presets">
            <at-button
                v-for="minutes in presets"
                :key="minutes"
                :type="isSelected(minutes) ? 'primary' : ''"
                @click.prevent="select(minutes)"
            >
                {{ presetLabel(minutes) }}
            </at-button>
        </at-button-group>
        <at-input
            class="duration-input__custom"
            :value="displayValue"
            :placeholder="$t('time_intervals.duration.custom')"
            @input="select"
        >
            <template slot="append">
                <span>{{ $t('time_intervals.duration.minutes') }}</span>
            </template>
        </at-input>
        <small class="duration-input__hint">{{ $t('time_intervals.duration.hint') }}</small>
    </div>
</template>

<script>
    import { DURATION_PRESETS, formatDuration, parseDuration } from '../duration';

    export default {
        name: 'DurationInput',
        props: {
            inputHandler: {
                type: Function,
                required: true,
            },
            value: {
                type: [Number, String],
                default: null,
            },
        },
        data() {
            return {
                presets: DURATION_PRESETS,
            };
        },
        computed: {
            displayValue() {
                return formatDuration(this.value);
            },
        },
        methods: {
            isSelected(minutes) {
                return parseDuration(this.value) === minutes;
            },
            presetLabel(minutes) {
                return minutes % 60 === 0
                    ? this.$tc('time_intervals.duration.hours', minutes / 60, { count: minutes / 60 })
                    : this.$t('time_intervals.duration.minutes_short', { count: minutes });
            },
            select(value) {
                this.inputHandler(value);
                this.$emit('change', value);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .duration-input {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5em 1em;

        &__custom {
            width: 160px;
        }

        &__hint {
            flex-basis: 100%;
            color: #b1b1be;
        }
    }
</style>
