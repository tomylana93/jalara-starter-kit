<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="public/assets/images/branding/logo-dark.png">
    <source media="(prefers-color-scheme: light)" srcset="public/assets/images/branding/logo.png">
    <img alt="Jalara Starter Kit Logo" src="public/assets/images/branding/logo.png" width="320">
  </picture>
</p>

# Jalara Starter Kit

A professional and production-ready Laravel application starter kit. Jalara provides a highly-polished, feature-rich foundation with integrated authentication, role-based authorization, comprehensive application settings, dynamic branding, and a complete testing suite.

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.5-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.5">
  <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13">
  <img src="https://img.shields.io/badge/Inertia-3-9553E9?style=flat-square&logo=inertia&logoColor=white" alt="Inertia.js 3">
  <img src="https://img.shields.io/badge/Vue-3-4FC08D?style=flat-square&logo=vue.js&logoColor=white" alt="Vue 3">
  <img src="https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=flat-square&logo=tailwindcss&logoColor=white" alt="Tailwind CSS 4">
  <img src="https://img.shields.io/badge/Tests-568-blue?style=flat-square" alt="Tests">
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green?style=flat-square" alt="License"></a>
</p>

## Key Features

- **Robust Authentication & Security**: Complete user registration, secure session management, and two-factor authentication (2FA) powered by Laravel Fortify.
- **Dynamic Settings & Customization**: Comprehensive administration panels for system configuration, including general settings, authentication options, mail servers, user provisioning, security parameters, and dynamic brand assets.
- **Granular Authorization**: Comprehensive roles and permissions system built with Spatie Laravel Permission.
- **Type-Safe Routing**: End-to-end integration via Laravel Wayfinder, enabling auto-generation of strongly-typed route helpers directly in Vue.
- **Modern SPA Architecture**: Fast and seamless user interfaces developed using Vue 3 and styled with Tailwind CSS 4, connected seamlessly via Inertia.js 3.

## Technology Stack

### Backend
- **Core Framework**: Laravel 13
- **PHP Version**: PHP 8.5
- **Authentication Engine**: Laravel Fortify
- **Authorization Engine**: Spatie Laravel Permission
- **Settings Store**: Spatie Laravel Settings

### Frontend
- **SPA Bridge**: Inertia.js 3
- **UI Library**: Vue 3
- **Styling**: Tailwind CSS 4

### Testing & Quality
- **Backend Testing**: Pest 5
- **Frontend Testing**: Vitest
- **End-to-End Testing**: Playwright

## Quality Assurance

Jalara is built with stability and software quality as first-class citizens. The codebase is backed by a multi-tiered validation suite comprising 568 tests spanning unit, feature, and end-to-end verification. Automated analysis via Larastan and Rector ensures the backend remains robust and modern, while ESLint and Prettier format and check the Vue components.

## License

This project is open-source software licensed under the [MIT License](LICENSE).
