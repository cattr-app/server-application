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
                    localStorage.getItem(selectionModeKey) === 'all' &&
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

                // Legacy ID lists are ambiguous (all projects or a subset). Migrate them
                // to "custom" and keep their historical IDs so formerly selected projects
                // remain visible without expanding the filter to excluded projects.
                const useAllProjects = storedMode === 'all' || (storedMode === null && storedSelection === null);
                if (useAllProjects) {
                    this.selectedProjectIds = allProjectIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));
                    localStorage.setItem(selectionModeKey, 'all');
                    this.emitSelection(true);
                    this.$nextTick(() => this.$emit('loaded'));
                    return;
                }

                if (storedMode === null) {
                    localStorage.setItem(selectionModeKey, 'custom');
                }

                // Preserve selected IDs even if their projects are no longer available.
                // The backend authorizes historical intervals independently of membership.
                if (!Array.isArray(this.selectedProjectIds)) {
                    this.selectedProjectIds = [];
                }

                this.emitSelection();
                this.$nextTick(() => this.$emit('loaded'));
            },
            selectedProjects(values) {
                // MultiSelect also emits its initial model. Do not interpret that
                // unchanged value as an explicit user request to switch modes.
                const unchanged =
                    this.selectedProjectIds !== null &&
                    values.length === this.selectedProjectIds.length &&
                    values.every(id => this.selectedProjectIds.includes(id));

                if (!unchanged) {
                    this.selectedProjectIds = values;
                    localStorage.setItem(localStorageKey, JSON.stringify(values));

                    const allSelected =
                        this.ids.length > 0 &&
                        values.length === this.ids.length &&
                        this.ids.every(id => values.includes(id));

                    localStorage.setItem(selectionModeKey, allSelected ? 'all' : 'custom');
                }

                this.emitSelection(localStorage.getItem(selectionModeKey) === 'all');
            },
        },
    };
</script>
