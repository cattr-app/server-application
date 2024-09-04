<template>
    <div class="activity-wrapper">
        <div class="sort-wrapper">
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
        <div ref="commentForm" class="comment-form" :class="{ 'comment-form--at-bottom': sort === 'asc' }">
            <div class="comment-content" :class="{ 'comment-content--preview': mainPreview }">
                <div class="preview-btn-wrapper">
                    <at-button
                        v-show="commentMessage.length > 0"
                        class="preview-btn"
                        :icon="mainPreview ? 'icon-eye-off' : 'icon-eye'"
                        size="small"
                        circle
                        type="info"
                        hollow
                        @click="togglePreview(true)"
                    ></at-button>
                </div>
                <at-textarea
                    v-if="!mainPreview"
                    v-model="commentMessage"
                    class="comment-message mainTextArea"
                    autosize
                    resize="none"
                    @change="commentMessageChange"
                />
                <vue-markdown
                    v-else
                    ref="markdown"
                    :source="commentMessage"
                    :plugins="markdownPlugins"
                    :options="{ linkify: true }"
                />
            </div>
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
            <at-button class="comment-button" type="primary" @click.prevent="createComment(task.id)">
                {{ $t('projects.add_comment') }}
            </at-button>
        </div>
        <div ref="activities" class="history">
            <div v-for="item in activities" :key="item.id + (item.content ? 'c' : 'h')" class="comment">
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
                        <div v-if="item.user.id === user.id" class="comment-functions">
                            <div class="comment-buttons">
                                <at-button
                                    v-show="item.id === idComment"
                                    :icon="editPreview ? 'icon-eye-off' : 'icon-eye'"
                                    size="small"
                                    circle
                                    type="info"
                                    hollow
                                    @click="togglePreview(false)"
                                ></at-button>
                                <at-button
                                    :icon="item.id === idComment ? 'icon-x' : 'icon-edit-2'"
                                    size="small"
                                    circle
                                    type="warning"
                                    hollow
                                    @click="item.id === idComment ? cancelChangeComment() : changeComment(item)"
                                ></at-button>
                                <at-button
                                    icon="icon icon-trash-2"
                                    size="small"
                                    type="error"
                                    hollow
                                    circle
                                    @click="deleteComment(item)"
                                ></at-button>
                            </div>
                        </div>
                    </div>
                    <div v-if="item.id === idComment && !editPreview" ref="commentChangeForm" class="comment-content">
                        <at-textarea
                            v-model="changeMessageText"
                            :class="`commentTextArea${item.id}`"
                            class="comment-message"
                            autosize
                            resize="none"
                        />
                        <div class="comment-buttons">
                            <at-button class="comment-button" type="primary" @click.prevent="editComment(item)">
                                {{ $t('tasks.save_comment') }}
                            </at-button>
                            <at-button class="comment-button" @click.prevent="cancelChangeComment">
                                {{ $t('tasks.cancel') }}
                            </at-button>
                        </div>
                    </div>
                    <div
                        v-else
                        class="comment-content"
                        :class="{ 'comment-content--preview': editPreview && item.id === idComment }"
                    >
                        <template v-for="(content, index) in getCommentContent(item)">
                            <div :key="index">
                                <div v-if="content.type === 'text'">
                                    <vue-markdown
                                        ref="markdown"
                                        :source="content.text"
                                        :plugins="markdownPlugins"
                                        :options="{ linkify: true }"
                                    />
                                </div>
                                <span v-else-if="content.type === 'username'" class="username">{{ content.text }}</span>
                            </div>
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
    import VueMarkdown from '@/components/VueMarkdown';
    import 'markdown-it';
    import 'highlight.js/styles/github.min.css'; // Import a highlight.js theme (choose your favorite!)

    export default {
        components: {
            TeamAvatars,
            VueMarkdown,
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
                markdownPlugins: [
                    md => {
                        // Use a function to add the plugin
                        // Add the highlight.js plugin
                        md.use(require('markdown-it-highlightjs'), {
                            auto: true,
                            code: true,
                            // inline: true
                        });
                        md.use(require('markdown-it-sup'));
                        md.use(require('markdown-it-sub'));
                    },
                ],
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
                activities: [],
                page: 1,
                canLoad: true,
                observer: null,
                isModalOpen: false,
                mainPreview: false,
                editPreview: false,
            };
        },
        async created() {
            this.users = await this.userService.getAll();
            this.user = this.$store.state.user.user.data;
        },
        async mounted() {
            this.observer = new IntersectionObserver(this.infiniteScroll, {
                rootMargin: '100px',
                threshold: 0.8,
            });
            this.observer.observe(this.$refs.activities);

            this.statuses = await this.statusService.getAll();
            this.priorities = await this.priorityService.getAll();
            this.websocketEnterChannel(this.user.id, {
                create: async data => {
                    this.activities = (await this.getActivity()).data;
                },
                edit: async data => {
                    this.activities = (await this.getActivity()).data;
                },
            });
        },
        beforeDestroy() {
            this.observer.disconnect();
            this.websocketLeaveChannel(this.user.id);
        },
        computed: {
            visibleUsers() {
                return this.users.filter(user => {
                    return user.full_name.replace(/\s/g, '').toLocaleLowerCase().indexOf(this.userFilter) === 0;
                });
            },
        },
        watch: {
            sort() {
                this.resetHistory();
            },
            typeActivity() {
                this.resetHistory();
            },
        },
        methods: {
            fromNow,
            async resetHistory() {
                this.page = 1;
                this.canLoad = true;
                this.activities = [];
                this.observer.disconnect();
                this.observer.observe(this.$refs.activities);
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
            async infiniteScroll([entry]) {
                await this.$nextTick();
                if (entry.isIntersecting && this.canLoad === true) {
                    this.observer.disconnect();
                    this.canLoad = false;

                    let data = (await this.getActivity()).data;
                    this.page++;

                    if (data.length > 0) {
                        this.activities.push(...data);
                        this.canLoad = true;
                        this.observer.observe(this.$refs.activities);
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
                let content = item.content;
                if (item.id === this.idComment && this.editPreview) {
                    content = this.changeMessageText;
                }
                return content.split(/(@[0-9a-zа-я._-]+)/gi).map(str => {
                    return {
                        type: /^@[0-9a-zа-я._-]+/i.test(str) ? 'username' : 'text',
                        text: str,
                    };
                });
            },
            changeComment(item) {
                this.idComment = item.id;
                this.changeMessageText = item.content;
                this.editPreview = false;
                this.scrollToTextArea(`commentTextArea${this.idComment}`);
            },
            togglePreview(main = false) {
                if (main) {
                    this.mainPreview = !this.mainPreview;
                } else {
                    this.editPreview = !this.editPreview;
                }

                if (main && !this.mainPreview) {
                    this.scrollToTextArea('mainTextArea');
                } else if (!main && this.idComment && !this.editPreview) {
                    this.scrollToTextArea(`commentTextArea${this.idComment}`);
                }
            },
            scrollToTextArea(ref) {
                this.$nextTick(() => {
                    document.querySelector(`.${ref}`)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            },
            cancelChangeComment() {
                this.idComment = null;
                this.editPreview = false;
            },
            async editComment(item) {
                const newComment = { ...item, content: this.changeMessageText };
                const result = await this.taskActivityService.editComment(newComment);
                item.content = this.changeMessageText;
                item.updated_at = result.data.data.updated_at;
                this.changeMessageText = '';
                this.idComment = null;
            },
            async deleteComment(item) {
                if (this.modalIsOpen) {
                    return;
                }
                this.modalIsOpen = true;
                const isConfirm = await this.$CustomModal({
                    title: this.$t('notification.record.delete.confirmation.title'),
                    content: this.$t('notification.record.delete.confirmation.message'),
                    okText: this.$t('control.delete'),
                    cancelText: this.$t('control.cancel'),
                    showClose: false,
                    styles: {
                        'border-radius': '10px',
                        'text-align': 'center',
                        footer: {
                            'text-align': 'center',
                        },
                        header: {
                            padding: '16px 35px 4px 35px',
                            color: 'red',
                        },
                        body: {
                            padding: '16px 35px 4px 35px',
                        },
                    },
                    width: 320,
                    type: 'trash',
                    typeButton: 'error',
                });
                this.modalIsOpen = false;
                if (isConfirm === 'confirm') {
                    const result = await this.taskActivityService.deleteComment(item.id);
                    if (result.status === 204) {
                        this.activities.splice(this.activities.indexOf(item), 1);
                    }
                }
            },
            websocketLeaveChannel(userId) {
                this.$echo.leave(`tasks_activities.${userId}`);
                this.$echo.leave(`tasks.${userId}`);
            },
            websocketEnterChannel(userId, handlers) {
                const channelActivity = this.$echo.private(`tasks_activities.${userId}`);
                const channelTask = this.$echo.private(`tasks.${userId}`);
                for (const action in handlers) {
                    channelActivity.listen(`.tasks_activities.${action}`, handlers[action]);
                    channelTask.listen(`.tasks.${action}`, handlers[action]);
                }
            },
        },
    };
</script>

<style lang="scss" scoped>
    .activity-wrapper {
        display: flex;
        flex-direction: column;
    }
    .sort-wrapper {
        display: flex;
        justify-content: end;
    }
    .content {
        position: relative;
        .comment-functions {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 5;
            .comment-buttons {
                position: sticky;
                top: 10px;
                pointer-events: all;
                button {
                    background: #fff;
                }
            }
        }
    }
    .comment-content {
        position: relative;
        border: 1px solid transparent;
        border-radius: 4px;
        &--preview {
            border: 1px solid $color-info;
            border-radius: 4px;
        }
        &::v-deep {
            .preview-btn-wrapper {
                position: absolute;
                top: 5px;
                right: 5px;
                bottom: 5px;
                pointer-events: none;
            }
            .preview-btn {
                pointer-events: all;
                position: sticky;
                top: 10px;
                background: #fff;
            }
            img {
                max-width: 35%;
            }
            h6 {
                font-size: 14px;
            }
            hr {
                border: 0;
                border-top: 2px solid $gray-3;
                border-radius: 5px;
            }
            p {
                margin: 0 0 10px;
            }
            ul,
            ol {
                all: revert;
            }
            table {
                width: 100%;
                max-width: 100%;
                margin-bottom: 20px;
                border-collapse: collapse;
            }

            table > caption + thead > tr:first-child > th,
            table > colgroup + thead > tr:first-child > th,
            table > thead:first-child > tr:first-child > th,
            table > caption + thead > tr:first-child > td,
            table > colgroup + thead > tr:first-child > td,
            table > thead:first-child > tr:first-child > td {
                border-top: 0;
            }
            table > thead > tr > th {
                vertical-align: bottom;
                border-bottom: 2px solid #ddd;
            }
            table > tbody > tr:nth-child(odd) > td,
            table > tbody > tr:nth-child(odd) > th {
                background-color: #f9f9f9;
            }
            table > thead > tr > th,
            table > tbody > tr > th,
            table > tfoot > tr > th,
            table > thead > tr > td,
            table > tbody > tr > td,
            table > tfoot > tr > td {
                padding: 8px;
                line-height: 1.42857143;
                vertical-align: top;
                border-top: 1px solid #ddd;
            }
            code.hljs {
                white-space: pre;
                padding: 9.5px;
            }
            pre {
                white-space: pre !important;
                display: block;
                margin: 0 0 10px;
                font-size: 13px;
                line-height: 1.42857143;
                color: #333;
                word-break: break-all;
                word-wrap: break-word;
                background-color: #f5f5f5;
                border: 1px solid #ccc;
                border-radius: 4px;
                code {
                    padding: 0;
                    font-size: inherit;
                    color: inherit;
                    white-space: pre-wrap;
                    background-color: transparent;
                    border-radius: 0;
                }
            }
            code {
                padding: 2px 4px;
                font-size: 90%;
                color: #c7254e;
                background-color: #f9f2f4;
                border-radius: 4px;
            }
            blockquote {
                padding: 10px 20px;
                margin: 0 0 20px;
                font-size: 17.5px;
                border-left: 5px solid #eee;
            }
        }
    }

    .history {
        &-change {
            margin-top: 16px;
        }

        &-change-avatar {
            display: inline-block;
        }
    }
    .comment-form,
    .comment-content {
        &::v-deep .comment-message textarea {
            min-height: 140px !important;
            max-height: 500px;
            resize: none !important;
        }
    }
    .comment-form {
        width: 100%;
        margin-top: 16px;
        display: flex;
        flex-direction: column;

        &--at-bottom {
            order: 999;
        }

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
        margin-left: auto;
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
        &-menu__item {
            padding: 0;
            .at-radio {
                padding: 8px 16px;
                width: 100%;
            }
        }
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
