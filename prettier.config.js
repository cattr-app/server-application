module.exports = {
    printWidth: 120,
    tabWidth: 4,
    useTabs: false,
    semi: true,
    singleQuote: true,
    trailingComma: 'all',
    bracketSpacing: true,
    arrowParens: 'avoid',
    endOfLine: 'lf',
    vueIndentScriptAndStyle: true,
    overrides: [
        {
            files: '*.js',
            options: {
                parser: 'babel',
            },
        },
        {
            files: '*.ts',
            options: {
                parser: 'typescript',
            },
        },
        {
            files: '*.md',
            options: {
                parser: 'markdown',
            },
        },
        {
            files: '*.json',
            options: {
                parser: 'json',
            },
        },
        {
            files: '.prettierrc',
            options: {
                parser: 'json',
            },
        },
        {
            files: '.stylelintrc',
            options: {
                parser: 'json',
            },
        },
    ],
};
