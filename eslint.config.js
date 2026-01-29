import eslint from '@eslint/js';
import tseslint from '@typescript-eslint/eslint-plugin';
import tsparser from '@typescript-eslint/parser';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import prettier from 'eslint-plugin-prettier';
import eslintConfigPrettier from 'eslint-config-prettier';
import globals from 'globals';

export default [
	{
		ignores: [
			'build/**',
			'node_modules/**',
			'vendor/**',
			'*.php',
			'docs/**',
			'tests/**',
			'src/bannerComment.ts', // Concatenated into data-types.ts during build
			'output/data-types.ts', // Generated file
		],
	},
	{
		files: ['**/*.tsx', '**/*.ts'],
		languageOptions: {
			parser: tsparser,
			parserOptions: {
				ecmaVersion: 'latest',
				sourceType: 'module',
				ecmaFeatures: {
					jsx: true,
				},
				project: './tsconfig.json',
			},
			globals: {
				...globals.browser,
				...globals.es2021,
			},
		},
		plugins: {
			'@typescript-eslint': tseslint,
			react,
			'react-hooks': reactHooks,
			prettier,
		},
		settings: {
			react: {
				version: 'detect',
			},
		},
		rules: {
			// ESLint recommended
			...eslint.configs.recommended.rules,

			// TypeScript recommended
			...tseslint.configs.recommended.rules,

			// React recommended
			...react.configs.recommended.rules,

			// React Hooks
			...reactHooks.configs.recommended.rules,
			'react-hooks/immutability': 'off', // Allow DOM element manipulation in effects

			// Prettier - disables conflicting rules and shows prettier errors
			...eslintConfigPrettier.rules,
			'prettier/prettier': 'error',

			'@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
			'@typescript-eslint/no-empty-object-type': 'off', // Allow {} in generic constraints
			'react/no-unescaped-entities': 'off', // Allow apostrophes in i18n strings
		},
	},
];
