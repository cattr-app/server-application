import { store } from '@/store';

export function hasSelectedMain(value) {
    return value.toLowerCase() === store.getters['universalreport/selectedMain'];
}
