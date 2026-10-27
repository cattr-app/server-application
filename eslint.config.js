const js = require('@eslint/js');
const globals = require('globals');
const pluginVue = require('eslint-plugin-vue');
const prettierRecommended = require('eslint-plugin-prettier/recommended');

module.exports = [
    {
        ignores: ['resources/frontend/generated/**'],
    },
    {
        files: ['**/*.js', '**/*.vue'],
    },
    ...pluginVue.configs['flat/vue2-essential'],
    js.configs.recommended,
    prettierRecommended,
    {
        languageOptions: {
            ecmaVersion: 'latest',
            sourceType: 'module',
            globals: {
                ...globals.node,
                ...globals.browser,
            },
        },
        rules: {
            'linebreak-style': ['error', 'unix'],
            'prettier/prettier': 'error',
            'no-console': 'off',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'off',
            'no-unused-vars': 'off',
            // Added to eslint:recommended in ESLint 9; kept off to preserve the ESLint 8 rule set.
            'no-useless-assignment': 'off',
            'vue/multi-word-component-names': 'off',
            'vue/attributes-order': [
                'error',
                {
                    order: [
                        'DEFINITION',
                        'LIST_RENDERING',
                        'CONDITIONALS',
                        'RENDER_MODIFIERS',
                        'GLOBAL',
                        'UNIQUE',
                        'TWO_WAY_BINDING',
                        'OTHER_DIRECTIVES',
                        'OTHER_ATTR',
                        'EVENTS',
                        'CONTENT',
                    ],
                },
            ],
        },
    },
];
