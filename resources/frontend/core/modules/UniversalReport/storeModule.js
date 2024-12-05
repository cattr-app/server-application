import TasksService from '@/services/resource/task.service';
import UserService from '@/services/resource/user.service';
import UniversalReportService from './service/universal-report.service';
import ProjectsService from '@/services/resource/project.service';
import _ from 'lodash';

const service = new UniversalReportService();

const state = {
    name: '',
    type: '',
    service: null,
    bases: [],
    selectedBase: '',
    fields: {},
    selectedFields: {},
    dataObjects: [],
    selectedDataObjects: [],
    charts: [],
    selectedCharts: [],
    calendar: {
        type: '',
        start: '',
        end: '',
    },
    reports: {
        company: [],
        personal: [],
    },
};

const getters = {
    name: state => state.name,
    service: state => state.service,
    bases: state => state.bases,
    fields: state => state.fields,
    selectedBase: state => state.selectedBase,
    selectedFields: state => state.selectedFields,
    dataObjects: state => state.dataObjects,
    selectedDataObjects: state => state.selectedDataObjects,
    charts: state => state.charts,
    selectedCharts: state => state.selectedCharts,
    reports: state => state.reports,
    type: state => state.type,
    calendar: state => state.calendar,
};

const mutations = {
    setName(state, name) {
        state.name = name;
    },
    setService(state, service) {
        state.service = service;
    },
    setBases(state, bases) {
        state.bases = bases;
    },
    setBase(state, base) {
        state.selectedBase = base;
    },
    setFields(state, fields) {
        state.fields = fields;
    },
    setSelectedFields(state, selectedFields) {
        state.selectedFields = selectedFields;
    },
    setDataObjects(state, dataObjects) {
        state.dataObjects = dataObjects;
    },
    setSelectedDataObject(state, selectedDataObjects) {
        state.selectedDataObjects = selectedDataObjects;
    },
    setCharts(state, charts) {
        state.charts = charts;
    },
    setSelectedCharts(state, selectedCharts) {
        state.selectedCharts = selectedCharts;
    },
    setCalendarData(state, data) {
        state.calendar = data;
    },
    setReports(state, reports) {
        state.reports = reports;
    },
    setType(state, type) {
        state.type = type;
    },
    clearStore(state) {
        state.name = '';
        state.type = '';
        state.selectedBase = '';
        state.fields = {};
        state.selectedFields = {};
        state.dataObjects = [];
        state.selectedDataObjects = [];
        state.charts = [];
        state.selectedCharts = [];
        state.calendar = {
            type: '',
            start: '',
            end: '',
        };
    },
};

const actions = {
    init(context) {
        context.commit(
            'setService',
            new UniversalReportService(context, new TasksService(), new UserService(), new ProjectsService()),
        );
        context.commit('clearStore');
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
