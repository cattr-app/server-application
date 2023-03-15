<template>
    <div class="comments">
        <div ref="commentForm" class="comment-form">
            <at-textarea v-model="commentMessage" class="comment-message" @change="commentMessageChange" />

            <div
                v-if="showUsers"
                class="comment-form-users"
                :style="{ top: `${usersTop - scrollTop - commentMessageScrollTop}px`, left: `${usersLeft}px` }"
            >
                <div
                    v-for="user in visibleUsers"
                    :key="user.id"
                    class="comment-form-user"
                    @click="insertUserName(user.full_name)"
                >
                    <team-avatars class="user-avatar" :users="[user]" />
                    {{ user.full_name }}
                </div>
            </div>

            <at-button class="comment-submit" @click.prevent="createComment(task.id)">
                {{ $t('projects.add_comment') }}
            </at-button>
        </div>

        <div v-for="comment in task.comments" :key="comment.id" class="comment">
            <div class="comment-header">
                <span class="comment-author">
                    <team-avatars class="comment-avatar" :users="[comment.user]" />
                    {{ comment.user.full_name }}
                </span>

                <span class="comment-date">{{ formatDate(comment.created_at) }}</span>
            </div>

            <div class="comment-content">
                <template v-for="(content, index) in getCommentContent(comment)">
                    <span v-if="content.type === 'text'" :key="index">{{ content.text }}</span>
                    <span v-else-if="content.type === 'username'" :key="index" class="username">{{
                        content.text
                    }}</span>
                </template>
            </div>
        </div>
    </div>
</template>

<script>
    import { offset } from 'caret-pos';
    import TeamAvatars from '@/components/TeamAvatars';
    import TaskCommentService from '@/services/resource/task-comment.service';
    import UsersService from '@/services/resource/user.service';
    import { formatDate } from '@/utils/time';

    export default {
        components: {
            TeamAvatars,
        },
        props: {
            task: {
                type: Object,
                required: true,
            },
        },
        inject: ['reload'],
        data() {
            return {
                taskCommentService: new TaskCommentService(),
                userService: new UsersService(),
                commentMessage: '',
                users: [],
                userFilter: '',
                userNameStart: 0,
                userNameEnd: 0,
                showUsers: false,
                usersTop: 0,
                usersLeft: 0,
                scrollTop: 0,
                commentMessageScrollTop: 0,
            };
        },
        computed: {
            visibleUsers() {
                return this.users.filter(user => {
                    return user.full_name.replace(/\s/g, '').toLocaleLowerCase().indexOf(this.userFilter) === 0;
                });
            },
        },
        methods: {
            formatDate,
            async createComment(id) {
                const comment = await this.taskCommentService.save({
                    task_id: id,
                    content: this.commentMessage,
                });

                this.commentMessage = '';

                if (this.reload) {
                    this.reload();
                }
            },
            commentMessageChange(value) {
                const textArea = this.$refs.commentForm.querySelector('textarea');
                const regexp = /@([0-9a-zа-я._-]*)/gi;
                let match,
                    found = false;
                while ((match = regexp.exec(value)) !== null) {
                    const start = match.index;
                    const end = start + match[0].length;
                    if (textArea.selectionStart >= start && textArea.selectionEnd <= end) {
                        this.userNameStart = start;
                        this.userNameEnd = end;
                        this.userFilter = match[1].replace(/\s/g, '').toLocaleLowerCase();
                        this.showUsers = true;

                        this.scrollTop = document.scrollingElement.scrollTop;
                        this.commentMessageScrollTop = textArea.scrollTop;

                        const coords = offset(textArea);
                        this.usersTop = coords.top + 20;
                        this.usersLeft = coords.left;

                        found = true;
                        break;
                    }
                }

                if (!found) {
                    this.showUsers = false;
                    this.userFilter = '';
                }
            },
            onScroll() {
                this.scrollTop = document.scrollingElement.scrollTop;
            },
            insertUserName(value) {
                const messageBefore = this.commentMessage.substring(0, this.userNameStart);
                const messageAfter = this.commentMessage.substring(this.userNameEnd);
                const userName = `@${value.replace(/[^0-9a-zа-я._-]/gi, '')}`;
                this.commentMessage = [messageBefore, userName, messageAfter].join('');

                this.$nextTick(() => {
                    const textArea = this.$refs.commentForm.querySelector('textarea');
                    textArea.focus();

                    textArea.selectionStart = this.userNameStart + userName.length;
                    textArea.selectionEnd = this.userNameStart + userName.length;

                    this.showUsers = false;
                    this.userFilter = '';
                });
            },
            getCommentContent(comment) {
                return comment.content.split(/(@[0-9a-zа-я._-]+)/gi).map(str => {
                    return {
                        type: /^@[0-9a-zа-я._-]+/i.test(str) ? 'username' : 'text',
                        text: str,
                    };
                });
            },
        },
        async created() {
            this.users = (await this.userService.getAll()).data;
        },
        mounted() {
            window.addEventListener('scroll', this.onScroll);
        },
        beforeDestroy() {
            window.removeEventListener('scroll', this.onScroll);
        },
    };
</script>

<style lang="scss" scoped>
    .comment-form {
        width: 100%;
        margin-top: 16px;

        &-users {
            position: fixed;
            background: #fff;
            border-radius: 4px;
            box-shadow: 0px 0px 10px rgba(63, 51, 86, 0.1);
            padding: 4px 0 4px;
            z-index: 10;
        }

        &-user {
            padding: 4px 8px 4px;
            cursor: pointer;

            &:hover {
                background: #ecf2fc;
            }
        }
    }

    .user-avatar {
        display: inline-block;
    }

    .comment-submit {
        margin-top: 8px;
    }

    .comment {
        display: block;
        margin-top: 16px;
        width: 100%;

        &-header {
            display: flex;
            justify-content: space-between;
        }

        &-avatar {
            display: inline-block;
        }

        .username {
            background: #ecf2fc;
            border-radius: 4px;
        }
    }
</style>
