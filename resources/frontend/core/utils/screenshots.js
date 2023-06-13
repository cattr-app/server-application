import { store } from '@/store';

/**
 * Check if state from env is valid and set disabled state for global screenshot selector
 */
export function checkLockedAndReturnValueForGlobalScreenshotsSelector(value, isDisabled = false) {
    let envValue = store.getters['user/companyData'].env_screenshots_state;

    if (typeof envValue === 'number' && envValue <= 2 && envValue >= 0) {
        value = envValue;
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

    if (envValue === 1 || envValue === 0) {
        value = envValue;
        isDisabled = true;
    } else if (envValue === 2) {
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

    if (envValue === 1 || envValue === 0) {
        value = envValue;
        isDisabled = true;
    } else if (envValue === 2) {
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

    if (companyData.env_screenshots_state !== 0) {
        if (companyData.env_screenshots_state === 1) {
            return true;
        }

        if (companyData.screenshots_state !== 0) {
            return true;
        }

        return false;
    }

    return false;
}

export function isHiddenHeaderScreenshotsLink(env_screenshots_state, screenshots_state) {
    return env_screenshots_state === 0 || (env_screenshots_state === -1 && screenshots_state === 0);
}
