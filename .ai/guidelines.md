# Guidelines

This file contains persistent guidelines for Junie to ensure consistency across the project.

### Project Context
- **Framework:** Laravel 12.0
- **PHP Version:** ^8.4
- **Frontend:** Livewire ^3.0 with Tailwind CSS and Alpine.js
- **Admin Panel:** Filament ^4.0
- **Testing:** PHPUnit ^11.0 with ParaTest ^7.8 run inside Docker (Laravel Sail)

### Coding Standards
- **PHP and Laravel:** Follow `.ai/rules/index.md` (`php.md` and `laravel.md`). Those are the conventions. Do not search the repo for them.
- **Frontend:** The main frontend is the Livewire components and views. Ignore (but do not remove) Vue and Inertia, they are vestigial previous versions waiting to be converted to Livewire.
- **Naming:** 
    - Controllers: `PascalCaseController`
    - Models: `PascalCase` (singular)
    - Migrations: `yyyy_mm_dd_hhmmss_description.php`
    - Components: `PascalCase.php`

### AI Collaboration Best Practices
1. **Specific Preferences:** Prefer the Livewire frontend components to be Volt single file components whenever possible. Also in Filament 4.x, Actions have moved namespaces from Filament\Tables\Actions\Action to Filament\Actions\Action. (this has come up more than once)
2. **Commit Messages:** Do not propose commits without my approval and explicit instruction.
3. **Documentation:** A PHP DocBlock is required for every public controller endpoint method.
4. **Testing Strategy:** See `.ai/rules/laravel.md`. Tests run inside Sail. `Tests\TestCase` uses `RefreshDatabase` (mysql and pgsql), including under ParaTest. Pull requests require 80% line coverage on changed `app/**/*.php` files.

[//]: # (5. **Architectural Decisions:** Note any project-specific architectural patterns &#40;e.g., "Use Service classes for complex business logic"&#41;.)

### How to update this file
- Add new guidelines as the project evolves.
- Keep instructions concise and actionable.
- Group related guidelines under appropriate headers.
