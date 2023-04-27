export function getParentElement(child, search) {
    if (child.parentElement.classList.contains(search)) {
        return child.parentElement;
    }

    return getParentElement(child.parentElement, search);
}

export function loadSections(context, router, requireSection) {
    const sections = requireSection
        .keys()
        .map(fn => requireSection(fn).default)
        .map(section => {
            if (typeof section === 'function') {
                return section(context, router);
            }

            return section;
        });

    sections.forEach(section => {
        if (Object.prototype.hasOwnProperty.call(section, 'scope') && section.scope === 'company') {
            context.addCompanySection(section);
        } else {
            context.addSettingsSection(section);
        }
    });
}
