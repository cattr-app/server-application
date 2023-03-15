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
            onLoad(allSelectOptions) {
                const allProjectIds = allSelectOptions.map(option => option.id);
                this.ids = allProjectIds;
                // Select all options if storage is empty
                if (!localStorage.getItem(localStorageKey)) {
                    this.selectedProjectIds = allProjectIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));
                    this.$emit('change', this.selectedProjectIds);
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

                this.$emit('change', this.selectedProjectIds);
                this.$nextTick(() => this.$emit('loaded'));
            },
            selectedProjects(values) {
                this.selectedProjectIds = values;
                localStorage.setItem(localStorageKey, JSON.stringify(this.selectedProjectIds));
                this.$emit('change', values);
            },
        },
    };
</script>
