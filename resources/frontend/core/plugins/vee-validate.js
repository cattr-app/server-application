import { extend, configure } from 'vee-validate';
import i18n from '@/i18n';
import * as validationRules from 'vee-validate/dist/rules';
import isEmail from 'validator/lib/isEmail';

for (const rule in validationRules) {
    extend(rule, validationRules[rule]);
}

configure({
    defaultMessage: (field, values) => {
        return i18n.t(`validation.${values._rule_}`, values);
    },
});

extend('email', value => isEmail(String(value), { require_tld: false }));
