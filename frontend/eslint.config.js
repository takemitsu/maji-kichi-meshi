import vue from 'eslint-plugin-vue'
import typescript from '@typescript-eslint/eslint-plugin'
import typescriptParser from '@typescript-eslint/parser'

export default [
    {
        ignores: [
            '.git/**',
            '.github/**',
            'node_modules/**',
            '.nuxt/**',
            '.output/**',
            'dist/**',
            'www/**',
            '*.scss',
            '*.css',
            'public/**',
            'coverage/**',
            'tsconfig.json',
            'jsconfig.json',
            'nuxt.config.ts',
            'package.json',
            'package-lock.json',
            'yarn.lock',
        ],
    },
    {
        files: ['**/*.{js,ts}'],
        plugins: {
            '@typescript-eslint': typescript,
        },
        languageOptions: {
            parser: typescriptParser,
            parserOptions: {
                ecmaVersion: 2022,
                sourceType: 'module',
            },
        },
        rules: {
            '@typescript-eslint/no-explicit-any': 'error',
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    vars: 'all',
                    args: 'after-used',
                    ignoreRestSiblings: false,
                    argsIgnorePattern: '^_',
                },
            ],
            '@typescript-eslint/no-empty-function': 'off',
            '@typescript-eslint/no-non-null-assertion': 'off',
            '@typescript-eslint/ban-ts-comment': [
                'error',
                {
                    'ts-expect-error': false,
                },
            ],
            quotes: ['error', 'single', { allowTemplateLiterals: true, avoidEscape: true }],
            semi: ['error', 'never'],
            'comma-dangle': ['error', 'always-multiline'],
            indent: 'off',
            'no-console': ['warn', { allow: ['warn', 'error', 'info'] }],
            'no-debugger': 'warn',
            'no-var': 'error',
            'prefer-const': 'error',
            'prefer-arrow-callback': 'error',
            'no-multiple-empty-lines': ['error', { max: 2 }],
            'no-unneeded-ternary': 'error',
            camelcase: 'off',
            'no-undef': 'off',
        },
    },
    ...vue.configs['flat/recommended'],
    {
        files: ['**/*.vue'],
        languageOptions: {
            parserOptions: {
                parser: typescriptParser,
                ecmaVersion: 2022,
                sourceType: 'module',
            },
        },
        plugins: {
            '@typescript-eslint': typescript,
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-unused-vars': 'error',
            'vue/html-self-closing': 'off',
            'vue/singleline-html-element-content-newline': 'off',
            'vue/multiline-html-element-content-newline': 'off',
            'vue/max-attributes-per-line': 'off',
            'vue/html-closing-bracket-newline': 'off',
            'vue/html-indent': 'off',
            'vue/script-indent': 'off',
            'vue/attributes-order': 'off',
            'vue/require-default-prop': 'off',
            'vue/no-v-html': 'off',
            'vue/require-explicit-emits': 'warn',
            '@typescript-eslint/no-explicit-any': 'error',
            '@typescript-eslint/no-unused-vars': [
                'error',
                {
                    vars: 'all',
                    args: 'after-used',
                    ignoreRestSiblings: false,
                    argsIgnorePattern: '^_',
                },
            ],
            '@typescript-eslint/no-empty-function': 'off',
            '@typescript-eslint/no-non-null-assertion': 'off',
            '@typescript-eslint/ban-ts-comment': [
                'error',
                {
                    'ts-expect-error': false,
                },
            ],
            quotes: ['error', 'single', { allowTemplateLiterals: true, avoidEscape: true }],
            semi: ['error', 'never'],
            'comma-dangle': ['error', 'always-multiline'],
            indent: 'off',
            'no-console': ['warn', { allow: ['warn', 'error', 'info'] }],
            'no-debugger': 'warn',
            'no-var': 'error',
            'prefer-const': 'error',
            'prefer-arrow-callback': 'error',
            'no-multiple-empty-lines': ['error', { max: 2 }],
            'no-unneeded-ternary': 'error',
            camelcase: 'off',
            'no-undef': 'off',
        },
    },
    {
        files: ['pages/**/*.vue'],
        rules: {
            'vue/multi-word-component-names': 'off',
        },
    },
]
