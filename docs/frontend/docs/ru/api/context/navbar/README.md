# Навигационная панель

> Добавление ссылок в навигационную панель
---
Чтобы пользователям было удобнее использовать навигацию, интерфейс управления ядром Cattr предоставляет разработчику возможность добавлять ссылки на определенные страницы в навигационную панель.

---

# Добавление ссылок в нав. панель

Чтобы добавить ссылку в навигационную панель, воспользуйтесь методом `addNavbarEntry` экземпляра `context`

```javascript
/**
 * Add navbar entry
 */
addNavbarEntry(...args) {
    Array.from(args).forEach(p => {
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

В качестве аргументов необходимо передать объект (ы) со следующим содержимым:

1. __label__ - отображаемое имя ссылки. _Обратите внимание, что Vue автоматически попытается перевести значение данного поля, если этого значения будет найден соответсвующий перевод. Подобрнее см. в разделе [Локализация](ru/api/context/localization/)._
2. __to__ - ___объект___ router-link, по которому Vue Router определяет, на какой компонент будет вести данная ссылка. Подробнее о router-link [смотрите на сайте офф. документации Vue Router](https://router.vuejs.org/api/#router-link-props)
3. __displayCondition__ - callback-функция, которая должна возвращать boolean-значение. Данная функция получает в качестве единственного аргумента экземляр Vuex Storage. Данная функция служит для проверки возможности отображения ссылки для конечного пользователя. Если функция возвращает false, ссылка отображена не будет.

?> Обратите внимание, что метод `addNavbarEntry` принимает __не__ массив объектов. Все конфигурации ссылок в данный метод передаются отдельными параметрами.

## Создание выпадающего меню с группой ссылкой

Чтобы добавить выпадающее меню с группой ссылок, воспользуйтесь методом `addNavbarEntryDropdown` экземпляра `context`

```javascript
/**
 * Add navbar Dropdown Entry
 */
addNavbarEntryDropDown(...args) {
    Array.from(args).forEach(p => {
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

В качестве аргументов, данный метод принимает такой же объект, как и в случае с методом `addNavbarEntry`, однако добавляется один параметр

* __section__ - строка, являющаяся идентификатором выпадающего меню. По данной строке рендер-модель определяет, в какую группу объединять ссылки

## Добавление ссылки в выпадающее меню юзера

Чтобы добавить ссылку, воспользуйтесь методом `addNavbarMenuEntryDropDown` экземпляра `context`

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

В качестве аргументов, данный метод принимает такой же объект, как и в случае с методом `addNavbarEntry`, однако нужно добавить в объект __to__ свойство __icon__. Доступные иконки можно найти тут [Icons](https://at-ui.github.io/at-ui/#/en/docs/icon)

## Пример

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

?> В данном примере в группу `navigation.dropdown.reports` будут добавлены две ссылки:
<br>1. navigation.time-use-report
<br>2. navigation.project-report

