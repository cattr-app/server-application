<template>
    <multi-select
        placeholder="control.project_selected"
        :inputHandler="selectedProjects"
        :selected="selectedProjectIds"
        :service="projectService"
        name="projects"
        :size="size"
        @onOptionsLoad="onLoad"
    >
    </multi-select>
</template>

<script>
    import MultiSelect from '@/components/MultiSelect';
    import ProjectService from '@/services/resource/project.service';

    const localStorageKey = 'amazingcat.local.storage.project_select';
    const selectionModeKey = `${localStorageKey}.mode`;

    export default {
        name: 'ProjectSelect',
        components: {
            MultiSelect,
        },
        props: {
            size: {
                type: String,
                default: 'normal',
            },
            value: {
                type: Array,
                default: null,
            },
            unfilteredWhenAllSelected: {
                type: Boolean,
                default: false,
            },
        },
        data() {
            const selectedProjectIds =
                this.value !== null ? this.value : JSON.parse(localStorage.getItem(localStorageKey));

            return {
                projectService: new ProjectService(),
                selectedProjectIds,
                ids: [],
            };
        },
        methods: {
            emitSelection(defaultToAll = false) {
                const allSelected =
                    this.selectedProjectIds.length === this.ids.length &&
                    (this.ids.length > 0 || defaultToAll) &&
                    this.ids.every(id => this.selectedProjectIds.includes(id));

                this.$emit('change', this.unfilteredWhenAllSelected && allSelected ? null : this.selectedProjectIds);
            },
            onLoad(allSelectOptions) {
                const allProjectIds = allSelectOptions.map(option => option.id);
                this.ids = allProjectIds;
                const storedMode = localStorage.getItem(selectionModeKey);
                const storedSelection = localStorage.getItem(localStorageKey);

                // The "all" choice must survive losing the last accessible project and page reloads.
                // For legacy selections without a mode, also recover when every saved project disappeared.
                const useAllProjects =
                    storedMode === 'all' ||
                    storedSelection === null ||
                    (storedMode === null && this.selectedProjectIds.length > 0 && allProjectIds.length === 0);
                if (useAllProjects) {
                    this.selectedProjectIds = allProjectIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));
                    localStorage.setItem(selectionModeKey, 'all');
                    this.emitSelection(true);
                    this.$nextTick(() => this.$emit('loaded'));
                    return;
                }

                // Remove options that no longer exists
                const existingProjectIds = this.selectedProjectIds.filter(projectId =>
                    allProjectIds.includes(projectId),
                );

                if (this.selectedProjectIds.length > existingProjectIds.length) {
                    this.selectedProjectIds = existingProjectIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));
                }

                // Upgrade a legacy all-project selection without changing explicitly stored subsets.
                if (
                    storedMode === null &&
                    allProjectIds.length > 0 &&
                    this.selectedProjectIds.length === allProjectIds.length &&
                    allProjectIds.every(id => this.selectedProjectIds.includes(id))
                ) {
                    localStorage.setItem(selectionModeKey, 'all');
                }

                this.emitSelection();
                this.$nextTick(() => this.$emit('loaded'));
            },
            selectedProjects(values) {
                this.selectedProjectIds = values;
                localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));

                const allSelected =
                    this.ids.length > 0 &&
                    values.length === this.ids.length &&
                    this.ids.every(id => values.includes(id));

                // MultiSelect can re-emit its initial empty model with no available projects.
                // That must not overwrite a persisted "all" default with an explicit empty choice.
                if (this.ids.length > 0 || values.length > 0 || localStorage.getItem(selectionModeKey) !== 'all') {
                    localStorage.setItem(selectionModeKey, allSelected ? 'all' : 'custom');
                }

                this.emitSelection(localStorage.getItem(selectionModeKey) === 'all');
            },
        },
    };
</script>
