import { store } from '@/store';

/**
 * Check if state from env is valid and set disabled state for global screenshot selector
 */
export function checkLockedAndReturnValueForGlobalScreenshotsSelector(value, envValue, isDisabled = false) {
    if (typeof envValue === 'number' && envValue <= 2 && envValue >= 0) {
        value = envValue;
        isDisabled = true;
    }

    return [value, isDisabled];
}

/**
 * Check if state from env & company is valid and set disabled state for admin screenshot selector
 */
export function checkLockedAndReturnValueForUsersScreenshotsSelector(value, envValue, companyValue, isDisabled) {
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
export function checkLockedAndReturnValueForAccountScreenshotsSelector(
    value,
    envValue,
    companyValue,
    isDisabled,
    isBlockedByAdmin,
) {
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
