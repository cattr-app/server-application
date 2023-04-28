import Grid from './builder/grid';
import Crud from './builder/crud';
import NavbarEntry from './builder/navbar';
import SettingsSection from './builder/sections';
import isObject from 'lodash/isObject';
import { store } from '@/store';

/**
 * Module class. This class represents the context of a module in module.init.js -> init() function.
 */
export default class Module {
    routes = [];
    navEntries = [];
    navEntriesDropdown = {};
    navEntriesMenuDropdown = [];
    settingsSections = [];
    companySections = [];
    locales = {};
    pluralizationRules = {};
    additionalFields = [];

    constructor(routerPrefix, moduleName) {
        this.routerPrefix = routerPrefix;
        this.moduleName = moduleName;
    }

    /**
     * Add locale code to allow custom locale select
     *
     * @param {String} code
     * @param {String} label
     *
     * @returns {Module}
     */
    addLocaleCode(code, label) {
        store.dispatch('lang/setLang', { code, label });

        return this;
    }

    /**
     * Add module to Vuex store
     *
     * @param {Object} vuexModule
     * @returns {Module}
     */
    registerVuexModule(vuexModule) {
        if (!isObject(vuexModule)) {
            throw new Error('Vuex Module must be an object.');
        }

        store.registerModule(this.moduleName.toLowerCase(), { ...vuexModule, namespaced: true });

        store.dispatch(`${this.moduleName.toLowerCase()}/init`);
        return this;
    }

    /**
     * Create GRID instance, which can be exported to RouterConfig
     * @param label
     * @param id
     * @param serviceClass
     * @param gridData
     * @param gridRouterPath
     * @returns {Grid}
     */
    createGrid(label, id, serviceClass, gridData = undefined, gridRouterPath = '') {
        return new Grid(label, id, serviceClass, this, gridData, gridRouterPath);
    }

    /**
     * Create CRUD instance, which can be exported to RouterConfig
     *
     * @param label
     * @param id
     * @param serviceClass
     * @param filters
     * @param defaultPrefix
     * @param pages
     * @returns {Crud}
     */
    createCrud(label, id, serviceClass, filters, defaultPrefix = '', pages = { edit: true, view: true, new: true }) {
        return new Crud(label, id, serviceClass, filters, this, defaultPrefix, pages);
    }

    /**
     * Add route to module-scoped routerConfig
     *
     * @param routerConfig
     * @returns {Module}
     */
    addRoute(routerConfig) {
        if (Array.isArray(routerConfig)) {
            routerConfig.forEach(p => {
                this.routes.push(p);
            });
        } else {
            this.routes.push(routerConfig);
        }
        return this;
    }

    /**
     * Add navbar entry
     */
    addNavbarEntry(...args) {
        Array.from(args).forEach(p => {
            this.navEntries.push(
                new NavbarEntry({
                    label: p.label,
                    to: p.to,
                    displayCondition: Object.prototype.hasOwnProperty.call(p, 'displayCondition')
                        ? p.displayCondition
                        : () => true,
                }),
            );
        });
    }

    /**
     * Add navbar Dropdown Entry
     */
    addNavbarEntryDropDown(...args) {
        Array.from(args).forEach(p => {
            if (!Object.prototype.hasOwnProperty.call(this.navEntriesDropdown, p.section)) {
                this.navEntriesDropdown[p.section] = [];
            }
            this.navEntriesDropdown[p.section].push(
                new NavbarEntry({
                    label: p.label,
                    to: p.to,
                    displayCondition: Object.prototype.hasOwnProperty.call(p, 'displayCondition')
                        ? p.displayCondition
                        : () => true,
                    section: p.section,
                }),
            );
        });
    }

    /**
     * Add to user menu entry of the navbar
     */
    addUserMenuEntry(...args) {
        Array.from(args).forEach(a => {
            this.navEntriesMenuDropdown.push(
                new NavbarEntry({
                    label: a.label,
                    to: a.to,
                    displayCondition: Object.prototype.hasOwnProperty.call(a, 'displayCondition')
                        ? a.displayCondition
                        : () => true,
                    icon: a.icon,
                }),
            );
        });
    }

    /**
     * Create new section with provided params
     */
    addSettingsSection(...args) {
        Array.from(args).forEach(({ route, accessCheck, scope, order, component }) => {
            const { path, name, meta, children } = route;
            const section = new SettingsSection(path, name, meta, accessCheck, scope, order, component, children);
            this.settingsSections.push(section);
        });
    }

    /**
     * Create new section with provided params
     */
    addCompanySection(...args) {
        Array.from(args).forEach(({ route, accessCheck, scope, order, component }) => {
            const { path, name, meta, children } = route;
            const section = new SettingsSection(path, name, meta, accessCheck, scope, order, component, children);
            this.companySections.push(section);
        });
    }

    addField(scope, path, field) {
        this.additionalFields.push({ scope, path, field });
    }

    /**
     * Add locales
     */
    addLocalizationData(locales) {
        this.locales = locales;
    }

    /**
     * Add pluralization rules
     */
    addPluralizationRules(rules) {
        this.pluralizationRules = rules;
    }

    /**
     * Init all available sections
     * @returns {Promise<void>[]}
     */
    initSettingsSections() {
        this.additionalFields
            .filter(({ scope }) => scope === 'settings')
            .forEach(({ scope, path, field }) => {
                store.dispatch('settings/addField', { scope, path, field });
            });

        return this.settingsSections.map(s => s.initSection());
    }

    /**
     * Init all available sections
     * @returns {Promise<void>[]}
     */
    initCompanySections() {
        this.additionalFields
            .filter(({ scope }) => scope === 'company')
            .forEach(({ scope, path, field }) => {
                store.dispatch('settings/addField', { scope, path, field });
            });

        return this.companySections.map(s => s.initSection());
    }

    /**
     * Init all available sections
     */
    reinitAllSections() {
        this.initSettingsSections();
        this.initCompanySections();
    }

    /**
     * Get all available to fill /settings route children
     * @returns {{path: string, component: null, meta, name: string}[]}
     */
    getSettingSectionsRoutes() {
        return this.settingsSections.map(s => s.getRoute());
    }

    /**
     * Get all available to fill /settings route children
     * @returns {{path: string, component: null, meta, name: string}[]}
     */
    getCompanySectionsRoutes() {
        return this.companySections.map(s => s.getRoute());
    }

    /**
     * Get Navigation bar entries array
     *
     * @returns {Array<Object>}
     */
    getNavbarEntries() {
        return this.navEntries;
    }

    /**
     * Get Navigation Dropdown bar entries array
     *
     * @returns {Array<Object>}
     */
    getNavbarEntriesDropdown() {
        return this.navEntriesDropdown;
    }

    /**
     * Get Navigation Menu Dropdown entries array
     *
     * @returns {Array<Object>}
     */
    getNavbarMenuEntriesDropDown() {
        return this.navEntriesMenuDropdown;
    }

    /**
     * Get module-scoped routes for Vue Router
     *
     * @returns {Array<Object>}
     */
    getRoutes() {
        return this.routes;
    }

    /**
     * Get locales
     *
     * @returns {Array}
     */
    getLocalizationData() {
        return this.locales;
    }

    /**
     * Get pluralization rules
     *
     * @returns {Object}
     */
    getPluralizationRules() {
        return this.pluralizationRules;
    }

    /**
     * Get module name
     *
     * @returns {string}
     */
    getModuleName() {
        return this.moduleName;
    }

    /**
     * Get module route name
     *
     * @returns {string}
     */
    getModuleRouteName() {
        return this.moduleName;
    }

    /**
     * Get router prefix for module-scoped routes
     *
     * @returns {string}
     */
    getRouterPrefix() {
        return this.routerPrefix;
    }
}
