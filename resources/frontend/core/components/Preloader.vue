<template>
    <div class="loader" :class="{ 'loader--transparent': isTransparent }">
        <div class="lds-ellipsis">
            <div />
            <div />
            <div />
            <div />
        </div>
    </div>
</template>

<script>
    export default {
        name: 'Preloader',
        props: {
            isTransparent: {
                type: Boolean,
                default: false,
            },
        },
    };
</script>

<style lang="scss" scoped>
    .loader {
        width: 100%;
        height: 100%;
        position: absolute;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 99;
        transition: all 1s ease-out;

        top: 0;
        right: 0;
        left: 0;
        bottom: 0;

        &--transparent {
            background: rgba(255, 255, 255, 0.8);
        }

        .lds-ellipsis {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ellipsis div {
            position: absolute;
            top: 33px;
            width: 13px;
            height: 13px;
            border-radius: 50%;
            background: $brand-blue-800;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }

        .lds-ellipsis div:nth-child(1) {
            left: 8px;
            animation: lds-ellipsis1 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(2) {
            left: 8px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(3) {
            left: 32px;
            animation: lds-ellipsis2 0.6s infinite;
        }

        .lds-ellipsis div:nth-child(4) {
            left: 56px;
            animation: lds-ellipsis3 0.6s infinite;
        }

        @keyframes lds-ellipsis1 {
            0% {
                transform: scale(0);
            }
            100% {
                transform: scale(1);
            }
        }

        @keyframes lds-ellipsis3 {
            0% {
                transform: scale(1);
            }
            100% {
                transform: scale(0);
            }
        }

        @keyframes lds-ellipsis2 {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(24px, 0);
            }
        }
    }
</style>
