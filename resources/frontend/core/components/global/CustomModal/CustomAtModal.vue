<template>
    <div>
        <transition name="fade">
            <div v-show="visible" class="at-modal__mask" @click="handleMaskClick"></div>
        </transition>
        <div
            class="at-modal__wrapper"
            :class="{
                'at-modal--hidden': !wrapShow,
                'at-modal--confirm': isIconType,
                [`at-modal--confirm-${type}`]: isIconType,
            }"
            @click.self="handleWrapperClick"
        >
            <transition name="fade">
                <div v-show="visible" class="at-modal" :style="modalStyle">
                    <div v-if="showHead && ($slots.header || this.title)" class="at-modal__header" :style="headerStyle">
                        <div class="at-modal__title">
                            <slot name="header">
                                <i v-if="isIconType" class="icon at-modal__icon" :class="iconClass" />
                                <p>{{ title }}</p>
                            </slot>
                        </div>
                    </div>
                    <div class="at-modal__body" :style="bodyStyle">
                        <slot>
                            <p>{{ content }}</p>
                            <div v-if="showInput" class="at-modal__input">
                                <at-input
                                    ref="input"
                                    v-model="inputValue"
                                    :placeholder="inputPlaceholder"
                                    @keyup.enter.native="handleAction('confirm')"
                                ></at-input>
                            </div>
                        </slot>
                    </div>
                    <div v-if="showFooter" class="at-modal__footer" :style="footerStyle">
                        <slot name="footer">
                            <at-button v-show="showCancelButton" @click.native="handleAction('cancel')"
                                >{{ localeCancelText }}
                            </at-button>
                            <at-button
                                v-show="showConfirmButton"
                                :type="typeButton"
                                @click.native="handleAction('confirm')"
                                >{{ localeOKText }}
                            </at-button>
                        </slot>
                    </div>
                    <span v-if="showClose" class="at-modal__close" @click="handleAction('cancel')"
                        ><i class="icon icon-x"></i
                    ></span>
                </div>
            </transition>
        </div>
    </div>
</template>

<script>
    import { t } from '@cattr/ui-kit/src/locale';

    export default {
        name: 'custom-at-modal',
        props: {
            title: String,
            content: String,
            value: {
                type: Boolean,
                default: false,
            },
            cancelText: {
                type: String,
            },
            okText: {
                type: String,
            },
            maskClosable: {
                type: Boolean,
                default: true,
            },
            showHead: {
                type: Boolean,
                default: true,
            },
            showClose: {
                type: Boolean,
                default: true,
            },
            showFooter: {
                type: Boolean,
                default: true,
            },
            showInput: {
                type: Boolean,
                default: false,
            },
            width: {
                type: [Number, String],
                default: 520,
            },
            closeOnPressEsc: {
                type: Boolean,
                default: true,
            },
            styles: {
                type: Object,
                default() {
                    return {};
                },
            },
            type: String,
            typeButton: {
                type: String,
                default: 'primary',
            },
        },
        data() {
            return {
                wrapShow: false,
                showCancelButton: true,
                showConfirmButton: true,
                action: '',
                visible: this.value,
                inputValue: null,
                inputPlaceholder: '',
                callback: null,
            };
        },
        computed: {
            headerStyle() {
                return Object.prototype.hasOwnProperty.call(this.styles, 'header') ? this.styles.header : {};
            },
            footerStyle() {
                return Object.prototype.hasOwnProperty.call(this.styles, 'footer') ? this.styles.footer : {};
            },
            bodyStyle() {
                return Object.prototype.hasOwnProperty.call(this.styles, 'body') ? this.styles.body : {};
            },
            iconClass() {
                const classArr = {
                    success: 'icon-check-circle',
                    error: 'icon-x-circle',
                    warning: 'icon-alert-circle',
                    info: 'icon-info',
                    trash: 'icon-trash-2',
                };

                return classArr[this.type] || '';
            },
            isIconType() {
                return ['success', 'error', 'warning', 'info', 'trash'].indexOf(this.type) > -1;
            },
            modalStyle() {
                const style = {};
                const styleWidth = {
                    width: `${this.width}px`,
                };

                Object.assign(style, styleWidth, this.styles);

                return style;
            },
            localeOKText() {
                return typeof this.okText === 'undefined' ? t('at.modal.okText') : this.okText;
            },
            localeCancelText() {
                return typeof this.cancelText === 'undefined' ? t('at.modal.cancelText') : this.cancelText;
            },
        },
        watch: {
            value(val) {
                this.visible = val;
            },
            visible(val) {
                if (val) {
                    if (this.timer) {
                        clearTimeout(this.timer);
                    }
                    this.wrapShow = true;
                } else {
                    this.timer = setTimeout(() => {
                        this.wrapShow = false;
                    }, 300);
                }
            },
        },
        methods: {
            doClose() {
                this.visible = false;
                this.$emit('input', false);
                this.$emit('on-cancel');

                if (this.action && this.callback) {
                    this.callback(this.action, this);
                }
            },
            handleMaskClick(evt) {
                if (this.maskClosable) {
                    this.doClose();
                }
            },
            handleWrapperClick(evt) {
                if (this.maskClosable) {
                    this.doClose();
                }
            },
            handleAction(action) {
                this.action = action;

                if (action === 'confirm') {
                    this.$emit('input', false);
                    this.$emit('on-confirm');
                }

                this.doClose();
            },
            handleKeyCode(evt) {
                if (this.visible && this.showClose) {
                    if (evt.keyCode === 27) {
                        // Escape
                        this.doClose();
                    }
                }
            },
        },
        mounted() {
            if (this.visible) {
                this.wrapShow = true;
            }

            document.addEventListener('keydown', this.handleKeyCode);
        },
        beforeDestory() {
            document.removeEventListener('keydown', this.handleKeyCode);
        },
    };
</script>
