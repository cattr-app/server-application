<template>
    <div>
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
            <div class="buttons">
                <at-button class="comment-button" @click.prevent="createComment(task.id)">
                    {{ $t('projects.add_comment') }}
                </at-button>
                <at-dropdown>
                    <at-button size="middle">
                        {{ $t('tasks.sort_or_filter') }} <i class="icon icon-chevron-down"></i>
                    </at-button>
                    <at-dropdown-menu slot="menu">
                        <at-radio-group v-model="sort">
                            <at-dropdown-item>
                                <at-radio label="desc">{{ $t('tasks.newest_first') }}</at-radio>
                            </at-dropdown-item>
                            <at-dropdown-item>
                                <at-radio label="asc">{{ $t('tasks.oldest_first') }}</at-radio>
                            </at-dropdown-item>
                        </at-radio-group>
                        <at-radio-group v-model="typeActivity">
                            <at-dropdown-item divided>
                                <at-radio label="all">{{ $t('tasks.show_all_activity') }}</at-radio>
                            </at-dropdown-item>
                            <at-dropdown-item>
                                <at-radio label="comments">{{ $t('tasks.show_comments_only') }}</at-radio>
                            </at-dropdown-item>
                            <at-dropdown-item>
                                <at-radio label="history">{{ $t('tasks.show_history_only') }}</at-radio>
                            </at-dropdown-item>
                        </at-radio-group>
                    </at-dropdown-menu>
                </at-dropdown>
            </div>
        </div>
        <div class="history">
            <div v-for="item in activity" :key="item.id + (item.content ? 'c' : 'h')" class="comment">
                <div v-if="!item.content" class="content">
                    <TeamAvatars class="history-change-avatar" :users="[item.user]" />

                    <template v-if="item.field === 'users'">
                        {{
                            $t('projects.task_change_users', {
                                user: item.user.full_name,
                                date: fromNow(item.created_at),
                                value:
                                    item.new_value && item.new_value.length
                                        ? JSON.parse(item.new_value)
                                              .map(user => user.full_name)
                                              .join(', ')
                                        : '',
                            })
                        }}
                    </template>

                    <template v-else-if="item.field === 'status_id'">
                        {{
                            $t('projects.task_change_to', {
                                user: item.user.full_name,
                                field: $t(`field.${item.field}`).toLocaleLowerCase(),
                                value: getStatusName(item.new_value),
                                date: fromNow(item.created_at),
                            })
                        }}
                    </template>

                    <template v-else-if="item.field === 'priority_id'">
                        {{
                            $t('projects.task_change_to', {
                                user: item.user.full_name,
                                field: $t(`field.${item.field}`).toLocaleLowerCase(),
                                value: getPriorityName(item.new_value),
                                date: fromNow(item.created_at),
                            })
                        }}
                    </template>

                    <template v-else-if="item.field !== 'relative_position'">
                        {{
                            $t('projects.task_change', {
                                user: item.user.full_name,
                                field: $t(`field.${item.field}`).toLocaleLowerCase(),
                                date: fromNow(item.created_at),
                            })
                        }}
                    </template>
                </div>
                <div v-if="item.content" class="content">
                    <div class="comment-header">
                        <span class="comment-author">
                            <team-avatars class="comment-avatar" :users="[item.user]" />
                            {{ item.user.full_name }} ·
                            <span class="comment-date">{{ fromNow(item.created_at) }}</span>
                        </span>
                        <div v-if="item.user.id === user.id" class="commment-functions">
                            <div class="comment-buttons">
                                <i class="icon icon-edit-2" @click="changeComment(item)"></i>
                                <i class="icon icon-x" @click="deleteComment(item)"></i>
                            </div>
                        </div>
                    </div>
                    <div v-if="item.id === idComment" ref="commentChangeForm" class="comment-content">
                        <at-textarea v-model="changeMessageText" class="comment-message" />
                        <div class="comment-buttons">
                            <at-button class="comment-button" @click.prevent="editComment(item)">
                                {{ $t('tasks.save_comment') }}
                            </at-button>
                            <at-button class="comment-button" @click.prevent="cancelChangeComment">
                                {{ $t('tasks.cancel') }}
                            </at-button>
                        </div>
                    </div>
                    <div v-else class="comment-content">
                        <template v-for="(content, index) in getCommentContent(item)">
                            <span v-if="content.type === 'text'" :key="index">{{ content.text }}</span>
                            <span v-else-if="content.type === 'username'" :key="index" class="username">{{
                                content.text
                            }}</span>
                        </template>
                    </div>
                    <span v-if="item.updated_at !== item.created_at" class="comment-date">
                        {{ $t('tasks.edited') }} {{ fromNow(item.updated_at) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import TeamAvatars from '@/components/TeamAvatars';
    import StatusService from '@/services/resource/status.service';
    import PriorityService from '@/services/resource/priority.service';
    import { offset } from 'caret-pos';
    import TaskActivityService from '@/services/resource/task-activity.service';
    import UsersService from '@/services/resource/user.service';
    import { fromNow } from '@/utils/time';

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
                statusService: new StatusService(),
                priorityService: new PriorityService(),
                taskActivityService: new TaskActivityService(),
                statuses: [],
                priorities: [],
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
                user: null,
                idComment: null,
                changeMessageText: null,
                sort: 'desc',
                typeActivity: 'all',
                activity: [],
                page: 1,
                canLoad: true,
            };
        },
        async created() {
            this.users = await this.userService.getAll();
            this.activity = (await this.getActivity()).data;
            this.user = this.$store.state.user.user.data;
        },
        async mounted() {
            window.addEventListener('scroll', this.onScroll);
            this.statuses = await this.statusService.getAll();
        },
        beforeDestroy() {
            window.removeEventListener('scroll', this.onScroll);
        },
        computed: {
            visibleUsers() {
                return this.users.filter(user => {
                    return user.full_name.replace(/\s/g, '').toLocaleLowerCase().indexOf(this.userFilter) === 0;
                });
            },
        },
        watch: {
            sort(newQuestion, oldQuestion) {
                this.resetHistory();
            },
            typeActivity(newQuestion, oldQuestion) {
                this.resetHistory();
            },
        },
        methods: {
            fromNow,
            async resetHistory() {
                this.page = 1;
                this.canLoad = true;
                this.activity = (await this.getActivity()).data;
            },
            async getActivity(dataOptions = {}) {
                return (
                    await this.taskActivityService.getActivity({
                        page: this.page,
                        orderBy: ['created_at', this.sort],
                        where: { task_id: ['=', [this.task.id]] },
                        task_id: this.task.id,
                        with: ['user'],
                        type: this.typeActivity,
                        ...dataOptions,
                    })
                ).data;
            },
            getStatusName(id) {
                const status = this.statuses.find(status => +status.id === +id);
                if (status) {
                    return status.name;
                }

                return '';
            },
            getPriorityName(id) {
                const priority = this.priorities.find(priority => +priority.id === +id);
                if (priority) {
                    return priority.name;
                }

                return '';
            },
            async createComment(id) {
                const comment = await this.taskActivityService.saveComment({
                    task_id: id,
                    content: this.commentMessage,
                });

                if (this.sort === 'desc') {
                    this.resetHistory();
                } else {
                    this.canLoad = true;
                }
                this.commentMessage = '';
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
            async onScroll() {
                this.scrollTop = document.scrollingElement.scrollTop;
                let bottomOfWindow =
                    document.documentElement.scrollHeight -
                    document.documentElement.scrollTop -
                    document.documentElement.clientHeight;
                if (bottomOfWindow <= 0 && this.canLoad === true) {
                    this.page++;
                    let data = (await this.getActivity()).data;

                    if (data.length > 0) {
                        this.activity = [...this.activity, ...data];
                    } else {
                        this.canLoad = false;
                    }
                }
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
            getCommentContent(item) {
                return item.content.split(/(@[0-9a-zа-я._-]+)/gi).map(str => {
                    return {
                        type: /^@[0-9a-zа-я._-]+/i.test(str) ? 'username' : 'text',
                        text: str,
                    };
                });
            },
            changeComment(item) {
                this.idComment = item.id;
                this.changeMessageText = item.content;
            },
            cancelChangeComment() {
                this.idComment = null;
            },
            async editComment(item) {
                const newCommnet = { ...item, content: this.changeMessageText };
                const result = await this.taskActivityService.editComment(newCommnet);
                item.content = this.changeMessageText;
                item.updated_at = result.data.data.updated_at;
                this.changeMessageText = '';
                this.idComment = null;
            },
            async deleteComment(item) {
                const result = await this.taskActivityService.deleteComment(item.id);
                this.activity.splice(this.activity.indexOf(item), 1);
            },
        },
    };
</script>

<style lang="scss" scoped>
    .history {
        &-change {
            margin-top: 16px;
        }

        &-change-avatar {
            display: inline-block;
        }
    }
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

    .comment-button {
        margin-top: 8px;
        margin-right: 8px;
    }
    .buttons {
        display: flex;
        justify-content: space-between;
    }
    .at-dropdown-menu {
        display: flex;
        flex-direction: column;
    }
    .at-dropdown {
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
        &-date {
            opacity: 0.5;
        }
        .username {
            background: #ecf2fc;
            border-radius: 4px;
        }
    }
</style>
