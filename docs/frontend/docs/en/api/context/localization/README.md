# Локализация

> Add or change Cattr locales
- - -

Cattr supports translation and locale change out of the box. The app's localization can be done with adding the translation file to the application. Cattr uses [vue-i18n](https://kazupon.github.io/vue-i18n/) pacakge to support internationalization. Translation files are stored as JSON files. We recommend you to use the following localization algorithm:

1. Any text is presented in "codes". E.g. dashboard should be name like this: __navigation.dashboard__.
2. Localization file contains this code's translation. For exanple, the english translation should be added like that:

```json
{
    "navigation": {
        "dashboard": "Dashboard"
    }
}
```

- - -

# Add/change locales

To add/change locale, create the json file containing the translation info. We recommend using the "codes" format, making it easier for developers to provide additional localization. The filename should match the according language's code. E.g. __en__ for english, __ru__ for russian, etc.

?> Keep in mind that Cattr supports only English, Danish and Russian languages out of the box. If you need to add another language to the system, use the `addLocaleCode` method (more details bellow).

## `addLocalizationData`

Locale adding can be done with addLocalizationData method. The method's argument is object that contains locales' code-keys with translation objects as the according values. Cattr supports __grouping__ out of the box, as it uses [vue-i18n](https://kazupon.github.io/vue-i18n/) pacakge for internationalization. As an example, here's english localization's object:

```json
{
    "navigation": {
        "dashboard": {
            "timeline": {
                "label": "Timeline", 
                "user_time": "Total Time",
            }
        }
    }
}
```

... that's going to be transformed by vue-i18n into:

```json
{
    "navigation.dashboard.timeline.label": "Timeline",
    "navigation.dashboard.timeline.user_time": "Total Time"
}
```

> According to the example above, the __"navigation.dashboard.timeline.label"__ line will be displayed as __"Timeline"__, and __"navigation.dashboard.timeline.user_time"__  as __"Total Time"__

However if you don't need grouping in your module, you can use the default line to line translation.

Russian string tanslation example:

```json
{
    "Sample String": "Образец строки"
}
```

> According to the example above, the __"Sample String"__ will be displayed in Russian language as ___"Образец строки"___

To add the translations to the app, create the JSON translations file (see above for example) in the module's directory, and pass its containment as the language object's value:

```javascript
context.addLocalizationData({
    en: require('./locales/en.json')
});
```

> This example implies the created module's directory contains the `locales` one with `en.json` file in it. We recommend you to use the lookalike structure in the project.

As you can see from the example, `addLocalizationData`'s argument is object that contains locales' code-keys with translation objects as the according values.

?> We recomend __json__-files to store locales, but you can use the format your project needs.

!> Keep in mind that if you add the language that hasn't been presented in Cattr before (any, excluding english, russian and danish), you'll need to add the locale with the `addLocaleCode` method.

## `addLocaleCode`

Cattr supports only english (en), danish (dk), and russian (ru) languages out of the box. To add new language's support, use `addLocaleCode` method. Once you're done, a new locale will be available to users.
The method's first argument is the locale's code, the 2nd one is the displayed locale's name.

```javascript
context.addLocaleCode('de', 'Denmark');
```
