import { store } from '@/store';

/**
 * Check if state from env is valid and set disabled state for global screenshot selector
 */
export function checkLockedAndReturnValueForGlobalScreenshotsSelector(value, isDisabled = false) {
    let companyData = store.getters['user/companyData'];

    if (hasEnvState('forbidden') || hasEnvState('required') || hasEnvState('optional')) {
        value = companyData.env_screenshots_state;
        isDisabled = true;
    }

    return [value, isDisabled];
}

/**
 * Check if state from env & company is valid and set disabled state for admin screenshot selector
 */
export function checkLockedAndReturnValueForUsersScreenshotsSelector(value) {
    let companyData = store.getters['user/companyData'];

    let envValue = companyData.env_screenshots_state,
        companyValue = companyData.screenshots_state,
        isDisabled = mustInherited();
    if (hasEnvState('required') || hasEnvState('forbidden')) {
        value = envValue;
        isDisabled = true;
    } else if (hasEnvState('optional')) {
        isDisabled = false;
    } else if (isDisabled) {
        value = companyValue;
    }

    return [value, isDisabled];
}

/**
 * Check if state from env & company is valid, check isBlockedByAdmin state and set disabled state for personal screenshot selector
 */
export function checkLockedAndReturnValueForAccountScreenshotsSelector(value, isBlockedByAdmin) {
    let companyData = store.getters['user/companyData'];

    let envValue = companyData.env_screenshots_state,
        companyValue = companyData.screenshots_state,
        isDisabled = mustInherited();
    if (hasEnvState('required') || hasEnvState('forbidden')) {
        value = envValue;
        isDisabled = true;
    } else if (hasEnvState('optional')) {
        isDisabled = false;

        if (isBlockedByAdmin) {
            isDisabled = true;
        }
    } else if (isDisabled) {
        value = companyValue;
    } else if (isBlockedByAdmin) {
        isDisabled = true;
    }

    return [value, isDisabled];
}

/**
 * Check if state from .env not forbidden. Allow run next checks
 * Check if state from .env === required then always allow show screenshot
 * Check if state from company not forbidden. Allow show screenshot
 * Else disallow show screenshot
 */
export function checkEnvAndCompanyVariablesScreenshotsSelector() {
    if (hasEnvState('forbidden')) {
        return false;
    }

    if (hasEnvState('required')) {
        return true;
    }

    if (hasCompanyState('forbidden')) {
        return false;
    }

    if (hasCompanyState('required')) {
        return true;
    }

    return false;
}

export function hideScreenshotsInHeader() {
    // console.log(hasEnvState('forbidden'), 'env');
    return hasEnvState('forbidden') || (hasEnvState('any') && hasCompanyState('forbidden'));
}

export function hasCompanyState(stateName, states = store.getters['screenshots/states']) {
    if (typeof store.getters['user/companyData'].env_screenshots_state === 'undefined') {
        return undefined;
    }

    return store.getters['user/companyData'].screenshots_state === states[stateName];
}

export function hasEnvState(stateName, states = store.getters['screenshots/states']) {
    if (typeof store.getters['user/companyData'].env_screenshots_state === 'undefined') {
        return undefined;
    }

    return store.getters['user/companyData'].env_screenshots_state === states[stateName];
}

function mustInherited(states = store.state.screenshots.states) {
    let companyData = store.getters['user/companyData'],
        companyStateValue = companyData.screenshots_state,
        stateValues = store.getters['screenshots/states'];

    if (typeof companyStateValue === 'undefined') {
        let res;
        setTimeout(() => (res = mustInherited()), 500);

        return res;
    } else {
        return (
            states.find(state => state.value === companyStateValue).value === stateValues['required'] ||
            states.find(state => state.value === companyStateValue).value === stateValues['forbidden']
        );
    }
}
