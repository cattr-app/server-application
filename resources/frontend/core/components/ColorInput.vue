<template>
    <div class="color-input__item">
        <at-modal v-model="modal" :showHead="false" :showClose="false" :showFooter="false">
            <ChromePicker :value="value" @input="$emit('change', $event.hex)" />
        </at-modal>

        <div class="color-input__color">
            <div class="at-input__original" :style="{ background: value }" @click.prevent="modal = true" />
        </div>

        <at-button class="color-input__remove" @click.prevent="$emit('change', null)">
            <span class="icon icon-x" />
        </at-button>
    </div>
</template>

<script>
    import { Chrome } from 'vue-color';

    export default {
        components: {
            ChromePicker: Chrome,
        },
        props: {
            value: {},
        },
        data() {
            return {
                modal: false,
            };
        },
    };
</script>

<style lang="scss" scoped>
    .at-input__original {
        width: 170px;
        height: 40px;
        cursor: pointer;
        border-radius: 5px;
        padding: 0;
    }

    .color-input {
        &__item {
            display: flex;
            flex-flow: row nowrap;

            &::v-deep .at-modal {
                width: 225px !important;
            }

            &::v-deep .at-modal__body {
                padding: 0;
            }
        }

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
</style>
