<template>
    <div class="avatar">
        <avatar
            class="avatar__photo"
            :username="username"
            :size="size"
            :custom-style="styles"
            :background-color="backgroundColor"
            :src="src"
        />
        <div v-show="user.online" class="avatar__online-status" />
    </div>
</template>

<script>
    import md5 from 'js-md5';

    const BACKGROUND_COLORS = [
        '#F44336',
        '#FF4081',
        '#9C27B0',
        '#673AB7',
        '#3F51B5',
        '#2196F3',
        '#03A9F4',
        '#00BCD4',
        '#009688',
        '#4CAF50',
        '#8BC34A',
        '#CDDC39',
        '#FFC107',
        '#FF9800',
        '#FF5722',
        '#795548',
        '#9E9E9E',
        '#607D8B',
    ];

    const getInitials = username => {
        const parts = username.split(/[ -]/);
        let initials = parts.map(part => part.charAt(0)).join('');

        if (initials.length > 3 && initials.search(/[A-Z]/) !== -1) {
            initials = initials.replace(/[a-z]+/g, '');
        }

        return initials.substr(0, 3).toUpperCase();
    };

    // Same arithmetic as vue-avatar (css-tricks "lighten/darken color"), so the colours do not change
    const lightenColor = (hex, amount) => {
        let usePound = false;

        if (hex[0] === '#') {
            hex = hex.slice(1);
            usePound = true;
        }

        const clamp = value => Math.min(255, Math.max(0, value));
        const num = parseInt(hex, 16);
        const r = clamp((num >> 16) + amount);
        const b = clamp(((num >> 8) & 0x00ff) + amount);
        const g = clamp((num & 0x0000ff) + amount);

        return (usePound ? '#' : '') + (g | (b << 8) | (r << 16)).toString(16);
    };

    // Avatar with the API of vue-avatar 2.3.3: an image from `src`, initials on a colour picked by the
    // username length when there is no image or it fails to load. Replaced by AtAvatar of the ui-kit later.
    const Avatar = {
        name: 'Avatar',
        props: {
            username: String,
            initials: String,
            backgroundColor: String,
            color: String,
            customStyle: Object,
            inline: Boolean,
            size: {
                type: Number,
                default: 50,
            },
            src: String,
            rounded: {
                type: Boolean,
                default: true,
            },
            lighten: {
                type: Number,
                default: 80,
            },
            parser: {
                type: Function,
                default: getInitials,
                validator: parser => typeof parser('John', getInitials) === 'string',
            },
        },
        data() {
            return {
                imgError: false,
            };
        },
        computed: {
            isImage() {
                return !this.imgError && Boolean(this.src);
            },
            background() {
                return this.backgroundColor || BACKGROUND_COLORS[this.username.length % BACKGROUND_COLORS.length];
            },
            fontColor() {
                return this.color || lightenColor(this.background, this.lighten);
            },
            userInitial() {
                return this.isImage ? '' : this.initials || this.parser(this.username, getInitials);
            },
            style() {
                const { size } = this;
                const style = {
                    display: this.inline ? 'inline-flex' : 'flex',
                    width: `${size}px`,
                    height: `${size}px`,
                    borderRadius: this.rounded ? '50%' : 0,
                    lineHeight: `${size + Math.floor(size / 20)}px`,
                    fontWeight: 'bold',
                    alignItems: 'center',
                    justifyContent: 'center',
                    textAlign: 'center',
                    userSelect: 'none',
                };

                if (this.isImage) {
                    style.background = `transparent url('${this.src}') no-repeat scroll 0% 0% / ${size}px ${size}px content-box border-box`;
                } else {
                    style.backgroundColor = this.background;
                    style.font = `${Math.floor(size / 2.5)}px/${size}px Helvetica, Arial, sans-serif`;
                    style.color = this.fontColor;
                }

                return style;
            },
        },
        mounted() {
            if (!this.isImage) {
                this.$emit('avatar-initials', this.username, this.userInitial);
            }
        },
        render(h) {
            return h('div', { style: [this.style, this.customStyle], attrs: { 'aria-hidden': 'true' } }, [
                // not displayed: detects a failed load of the background image
                this.isImage
                    ? h('img', {
                          style: { display: 'none' },
                          attrs: { src: this.src },
                          on: { error: () => (this.imgError = true) },
                      })
                    : null,
                h('span', { style: this.isImage ? { display: 'none' } : null }, this.userInitial),
            ]);
        },
    };

    export default {
        name: 'UserAvatar',
        props: {
            size: {
                type: Number,
                default: 30,
            },
            borderRadius: {
                type: Number,
                default: 5,
            },
            user: {
                type: Object,
                required: true,
            },
        },
        components: {
            Avatar,
        },
        computed: {
            username() {
                if (!this.user || !this.user.full_name) {
                    return '';
                }

                return this.user.full_name;
            },
            email() {
                if (!this.user || !this.user.email) {
                    return '';
                }

                return this.user.email;
            },
            src() {
                if (this.user.email) {
                    const emailMD5 = md5(this.email);

                    return `https://www.gravatar.com/avatar/${emailMD5}?d=404`;
                }

                return null;
            },
            backgroundColor() {
                return !this.username ? '#eaeaea' : null;
            },
            styles() {
                return {
                    borderRadius: `${this.borderRadius}px`,
                };
            },
        },
    };
</script>

<style lang="scss" scoped>
    .avatar {
        position: relative;

        &__online-status {
            height: 7px;
            width: 7px;
            position: absolute;
            background: #6eceb2;
            border-radius: 100%;
            border: 1px solid white;
            right: 0;
            bottom: 0px;
        }
    }
</style>
