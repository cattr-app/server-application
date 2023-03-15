import AccountService from '../services/account.service';
import LanguageSelector from '@/components/LanguageSelector';

export function fieldsProvider() {
    return [
        {
            key: 'id',
            displayable: () => false,
        },
        {
            label: 'field.full_name',
            key: 'full_name',
            rules: 'required',
            fieldOptions: {
                placeholder: 'John Snow',
                type: 'input',
            },
        },
        {
            label: 'field.email',
            key: 'email',
            rules: 'required|email',
            fieldOptions: {
                disableAutocomplete: true,
                type: 'input',
                placeholder: 'user@email.com',
                frontendType: 'email',
            },
        },
        {
            label: 'field.password',
            key: 'password',
            fieldOptions: {
                type: 'input',
                disableAutocomplete: true,
                placeholder: '******',
                frontendType: 'password',
            },
        },
        // Please use ISO locales for values ISO 639-1
        {
            label: 'field.user_language',
            key: 'user_language',
            rules: 'required',
            render: (h, props) => {
                if (typeof props.currentValue === 'object') {
                    props.currentValue = 'en';
                }

                return h(LanguageSelector, {
                    props: {
                        value: props.currentValue,
                    },
                    on: {
                        setLanguage(lang) {
                            props.inputHandler(lang);
                        },
                    },
                });
            },
        },
    ];
}

export const config = { fieldsProvider };

export default {
    // Check if this section can be rendered and accessed, this param IS OPTIONAL (true by default)
    // NOTICE: this route will not be added to VueRouter AT ALL if this check fails
    // MUST be a function that returns a boolean
    accessCheck: () => true,

    order: 10,

    route: {
        // After processing this route will be named as 'settings.exampleSection'
        name: 'settings.user.account',

        // After processing this route can be accessed via URL 'settings/example'
        path: '/settings/account',

        meta: {
            // After render, this section will be labeled as 'Example Section'
            label: 'settings.account',

            // Service class to gather the data from API, should be an instance of Resource class
            service: new AccountService(),

            // Renderable fields array
            get fields() {
                return config.fieldsProvider();
            },
        },
    },
};
