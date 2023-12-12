import { store } from '@/store';

export function hasSelectedBase(value) {
    return value.toLowerCase() === store.getters['universalreport/selectedBase'];
}
