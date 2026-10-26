// Helpers for the CRUD forms (resources/frontend/core/views/Crud/EditView.vue).
import { expect } from '@playwright/test';

/** Row of an edit form by its label text ("Name", "Project", ...). */
export function field(page, label) {
    return page
        .locator('.crud__edit-view .data-entry')
        .filter({ has: page.locator('.label', { hasText: new RegExp(`^\\s*${label}\\s*\\*?\\s*$`) }) });
}

/** Pick an option of an AT-UI select (<at-select>) inside `scope`. */
export async function chooseAtSelect(scope, option) {
    await scope.locator('.at-select__selection').first().click();
    const item = scope.locator('.at-select__dropdown .at-select__option', { hasText: option }).first();
    await expect(item).toBeVisible();
    await item.click();
}

/** Type into a vue-select search inside `scope` and pick the first option matching `option`. */
export async function chooseVueSelect(page, scope, search, option = search) {
    await scope.locator('input.vs__search').fill(search);
    const item = page.locator('.vs__dropdown-option', { hasText: option }).first();
    await expect(item).toBeVisible();
    await item.click();
}
