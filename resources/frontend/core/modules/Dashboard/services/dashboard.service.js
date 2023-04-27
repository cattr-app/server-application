import axios from 'axios';
import ReportService from '@/services/report.service';
import moment from 'moment';

export default class DashboardService extends ReportService {
    constructor(context, taskService, userService) {
        super();
        this.context = context;

        this.taskService = taskService;
        this.userService = userService;
    }

    downloadReport(startAt, endAt, users, projects, userTimezone, format, sortCol, sortDir) {
        return axios.post(
            'report/dashboard/download',
            {
                start_at: startAt,
                end_at: endAt,
                users,
                projects,
                user_timezone: userTimezone,
                sort_column: sortCol,
                sort_direction: sortDir,
            },
            {
                headers: { Accept: format },
            },
        );
    }

    getReport(startAt, endAt, users, projects, userTimezone) {
        return axios.post('report/dashboard', {
            users,
            projects,
            start_at: startAt,
            end_at: endAt,
            user_timezone: userTimezone,
        });
    }

    unloadIntervals() {
        this.context.commit('setIntervals', []);
    }

    load(userIDs, projectIDs, startAt, endAt, userTimezone) {
        this.getReport(startAt, endAt, userIDs, projectIDs, userTimezone)
            .then(response => {
                if (!response) {
                    return;
                }

                const data = response.data.data;

                this.context.commit('setIntervals', data);

                if (!data) {
                    return;
                }

                const uniqueProjectIDs = new Set();
                const uniqueTaskIDs = new Set();
                Object.keys(data).forEach(userID => {
                    const userIntervals = data[userID];
                    userIntervals.forEach(interval => {
                        uniqueProjectIDs.add(interval.project_id);
                        uniqueTaskIDs.add(interval.task_id);
                    });
                });

                const promises = [];

                const taskIDs = [...uniqueTaskIDs];
                if (taskIDs.length) {
                    promises.push(this.loadTasks(taskIDs));
                }

                return Promise.all(promises);
            })
            .then(() => {
                return this.loadUsers();
            })
            .then(() =>
                this.context.commit(
                    'setUsers',
                    this.context.state.users.map(u => {
                        if (Object.prototype.hasOwnProperty.call(this.context.state.intervals, u.id)) {
                            const lastInterval = this.context.state.intervals[u.id].slice(-1)[0];

                            if (
                                Math.abs(
                                    moment(lastInterval.end_at).diff(
                                        moment().subtract(u.screenshot_interval || 1, 'minutes'),
                                        'seconds',
                                    ),
                                ) < 10
                            ) {
                                return {
                                    ...u,
                                    last_interval: lastInterval,
                                };
                            }
                        }

                        return { ...u, last_interval: null };
                    }),
                ),
            )
            .catch(e => {
                if (!axios.isCancel(e)) {
                    throw e;
                }
            });
    }

    loadUsers() {
        return this.userService
            .getAll({ headers: { 'X-Paginate': 'false' } })
            .then(response => {
                this.context.commit('setUsers', response);
                return response;
            })
            .catch(e => {
                if (!axios.isCancel(e)) {
                    throw e;
                }
            });
    }

    /**
     * @returns {Promise<AxiosResponse<T>>}
     * @param taskIDs
     * @param action
     */
    loadTasks(taskIDs) {
        return this.taskService
            .getWithFilters({
                id: ['=', taskIDs],
                with: 'project',
            })
            .then(response => {
                if (typeof response !== 'undefined') {
                    const { data } = response;
                    const tasks = data.data.reduce((tasks, task) => {
                        tasks[task.id] = task;
                        return tasks;
                    }, {});

                    this.context.commit('setTasks', tasks);

                    return tasks;
                }
            })
            .catch(e => {
                if (!axios.isCancel(e)) {
                    throw e;
                }
            });
    }

    sendInvites(emails) {
        return axios.post(`register/create`, emails);
    }
}
