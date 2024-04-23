<template>
    <div class="team-avatars">
        <div class="team-avatars__preview">
            <at-tooltip v-for="user of users.slice(0, 2)" :key="user.id" placement="top" :content="user.full_name">
                <user-avatar :user="user" class="team-avatars__avatar"></user-avatar>
            </at-tooltip>
            <at-popover placement="top" trigger="click">
                <div v-if="users.length > 2" class="team-avatars__placeholder team-avatars__avatar">
                    <span>+{{ users.slice(2).length }}</span>
                </div>
                <template slot="content">
                    <div class="tooltip__avatars">
                        <at-tooltip
                            v-for="user of users.slice(2)"
                            :key="user.id"
                            placement="top"
                            :content="user.full_name"
                        >
                            <user-avatar :user="user" class="team-avatars__avatar"></user-avatar>
                        </at-tooltip>
                    </div>
                </template>
            </at-popover>
        </div>
    </div>
</template>

<script>
    import UserAvatar from '@/components/UserAvatar.vue';

    export default {
        name: 'TeamAvatars',
        components: {
            UserAvatar,
        },
        props: {
            users: {
                required: true,
                type: Array,
            },
        },
    };
</script>

<style lang="scss" scoped>
    .team-avatars {
        &__preview {
            display: flex;
        }

        &__avatar {
            margin: $spacing-01;
        }

        &__placeholder {
            display: flex;
            width: 30px;
            height: 30px;
            border-radius: 5px;
            font:
                12px / 30px Helvetica,
                Arial,
                sans-serif;
            align-items: center;
            justify-content: center;
            text-align: center;
            user-select: none;
            background-color: rgb(158, 158, 158);
            color: rgb(238, 238, 238);
            cursor: pointer;
        }
    }

    .tooltip {
        &__avatars {
            display: flex;
            flex-wrap: wrap;
        }
    }
</style>
