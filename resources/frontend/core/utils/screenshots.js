import { store } from '@/store';

/**
 * Check if state from env is valid and set disabled state for global screenshot selector
 */
export function checkLockedAndReturnValueForGlobalScreenshotsSelector(value, isDisabled = false) {
    let companyData = store.getters['user/companyData'];

    if (
        hasEnvState(companyData, 'forbidden') ||
        hasEnvState(companyData, 'required') ||
        hasEnvState(companyData, 'optional')
    ) {
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
        isDisabled = companyData.screenshots_state_inherit;

    if (hasEnvState(companyData, 'required') || hasEnvState(companyData, 'forbidden')) {
        value = envValue;
        isDisabled = true;
    } else if (hasEnvState(companyData, 'optional')) {
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
        isDisabled = companyData.screenshots_state_inherit;

    if (hasEnvState(companyData, 'required') || hasEnvState(companyData, 'forbidden')) {
        value = envValue;
        isDisabled = true;
    } else if (hasEnvState(companyData, 'optional')) {
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
 * Check if state from .env not 0. Allow run next checks
 * Check if state from .env === 1 then always allow show screenshot
 * Check if state from company not 0. Allow show screenshot
 * Else disallow show screenshot
 */
export function checkEnvAndCompanyVariablesScreenshotsSelector() {
    let companyData = store.getters['user/companyData'];

    if (!hasEnvState(companyData, 'forbidden')) {
        if (hasEnvState(companyData, 'required')) {
            return true;
        }

        if (!hasCompanyState(companyData, 'forbidden')) {
            return true;
        }

        return false;
    }

    return false;
}

export function isHiddenHeaderScreenshotsLink(env_screenshots_state, screenshots_state) {
    return env_screenshots_state === 0 || (env_screenshots_state === -1 && screenshots_state === 0);
}

export function hasCompanyState(company, stateName) {
    return company.screenshots_state === store.getters['screenshots/states'][stateName];
}

export function hasEnvState(company, stateName) {
    return company.env_screenshots_state === store.getters['screenshots/states'][stateName];
}
