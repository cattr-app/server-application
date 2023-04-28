<template>
    <multi-select
        ref="select"
        placeholder="control.status_selected"
        :inputHandler="selectedStatuses"
        :selected="selectedStatusIds"
        :service="statusService"
        name="statuses"
        :size="size"
        @onOptionsLoad="onLoad"
    >
        <template v-slot:before-options>
            <li class="at-select__option" @click="selectAllOpen">
                {{ $t('control.select_all_open') }}
            </li>
            <li class="at-select__option" @click="selectAllClosed">
                {{ $t('control.select_all_closed') }}
            </li>
        </template>
    </multi-select>
</template>

<script>
    import MultiSelect from '@/components/MultiSelect';
    import StatusService from '@/services/resource/status.service';

    const localStorageKey = 'amazingcat.local.storage.status_select';

    export default {
        name: 'StatusSelect',
        components: {
            MultiSelect,
        },
        props: {
            size: {
                type: String,
                default: 'normal',
            },
        },
        data() {
            return {
                statusService: new StatusService(),
                selectedStatusIds: JSON.parse(localStorage.getItem(localStorageKey)),
            };
        },
        methods: {
            onLoad(allSelectOptions) {
                const allStatusIds = allSelectOptions.map(option => option.id);

                // Select all options if storage is empty
                if (!localStorage.getItem(localStorageKey)) {
                    this.selectedStatusIds = allStatusIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedStatusIds));
                    this.$emit('change', this.selectedStatusIds);
                    this.$nextTick(() => this.$emit('loaded'));
                    return;
                }

                // Remove options that no longer exists
                const existingStatusIds = this.selectedStatusIds.filter(statusId => allStatusIds.includes(statusId));

                if (this.selectedStatusIds.length > existingStatusIds.length) {
                    this.selectedStatusIds = existingStatusIds;
                    localStorage.setItem(localStorageKey, JSON.stringify(this.selectedStatusIds));
                }

                this.$emit('change', this.selectedStatusIds);
                this.$nextTick(() => this.$emit('loaded'));
            },
            selectedStatuses(values) {
                this.selectedStatusIds = values;
                localStorage.setItem(localStorageKey, JSON.stringify(this.selectedStatusIds));
                this.$emit('change', values);
            },
            selectAllOpen() {
                this.$refs.select.selectAll(item => item.active);
            },
            selectAllClosed() {
                this.$refs.select.selectAll(item => !item.active);
            },
        },
    };
</script>
