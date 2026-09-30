# Navigation panel

> Adding links to the navigation bar
---
Cattr lets you add links to the specific pages into the navigation bar, so the users' navigation would be more convenient.

---

# Adding links to the navigation panel

To add the link to the navigation panel, use the `context` instance's `addNavbarEntry` method.

```javascript
/**
 * Add navbar entry
 */
addNavbarEntry() {
    Array.from(arguments).forEach(p => {
        this.navEntries.push(
            new NavbarEntry(
                p.label, 
                p.to,
                p.hasOwnProperty('displayCondition') 
                    ? p.displayCondition 
                    : () => true
            )
        );
    });
}
```

As arguments you'll need to pass the object (s) that contain:

1. __label__ - displayed link's name. _Keep in mind that Vue will authomatically try to translate this field's name if there's an according translation for it. More details available in Localization section._
2. __to__ - __object__ router-link that Vue ROuter uses to determine what component will be available via this link. More info on router-link is available on [Vue Router official documentation](https://router.vuejs.org/api/#router-link-props) page.
3. __displayCondition__ - callback function that should return boolean value. The function's only argument is the Vuex Storage instance. The function is used to determine if the link can be displayed to user. If the function returns false, the link won't be displayed.

?> Keep in mind, that the `addNavbarEntry` method accepts __not__ the array of objects. All the links configurations should be passed via separate params.

## Creating dropdown menu with the group of links

To add the dropdown menu with the group of links, use the `context` instance's `addNavbarEntryDropdown` method

```javascript
/**
 * Add navbar Dropdown Entry
 */
addNavbarEntryDropDown() {
    Array.from(arguments).forEach(p => {
        if (!this.navEntriesDropdown.hasOwnProperty(p.section)) {
            this.navEntriesDropdown[p.section] = [];
        }
        this.navEntriesDropdown[p.section].push(
            new NavbarEntry(
                p.label, 
                p.to,
                p.hasOwnProperty('displayCondition') 
                    ? p.displayCondition 
                    : () => true, 
                p.section
            )
        );
    });
}
```

As a arguments this method accepts the same object as in `addNavbarEntry` method, but there's a additional param

* __section__ - dropdown menu's string ID. This string is used by the renderer-model to determine the group the links has to be connected into.

## Adding link to user menu entry

To add link use the `context` instance's `addItemToDropDownMainMenu` method

```javascript
/**
 * Add to user menu entry of the navbar
 */
addUserMenuEntry(...args) {
    Array.from(args).forEach(a => {
        this.navEntriesMenuDropdown.push(
            new NavbarEntry(
                a.label, 
                a.to, 
                a.hasOwnProperty('displayCondition') 
                    ? a.displayCondition 
                    : () => true
            ),
        );
    });
}
```

As a arguments this method accepts the same object as in `addNavbarEntry`, but need add in object __to__ property __icon__.
Available icons here [Icons](https://at-ui.github.io/at-ui/#/en/docs/icon)

## Example

```javascript
context.addNavbarEntryDropDown({
    label: 'navigation.time-use-report',
    section: 'navigation.dropdown.reports',
    to: {
        name: 'report.time-use'
    },
});

context.addNavbarEntryDropDown({
    label: 'navigation.project-report',
    section: 'navigation.dropdown.reports',
    to: {
        name: 'report.projects'
    },
});

context.addUserMenuEntry({
    label: 'navigation.company_settings',
    to: {
        name: 'Settings.company.general',
        icon: 'icon-settings',
    },
});
```

?> In this example the `navigation.dropdown.reports` group will have 2 new links:
<br>1. navigation.time-use-report
<br>2. navigation.project-report

