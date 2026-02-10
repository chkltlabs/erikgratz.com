# Guidelines

This file contains persistent guidelines for Junie to ensure consistency across the project.

### Project Context
- **Framework:** Laravel 12.0
- **PHP Version:** ^8.4
- **Frontend:** Livewire ^3.0 with Tailwind CSS and Alpine.js
- **Admin Panel:** Filament ^4.0
- **Testing:** PHPUnit ^11.0 with ParaTest ^7.8

### Coding Standards
- **PHP:** Follow PSR-12 and use Laravel's standard conventions. Use typed properties and return types where possible.
- **Frontend:** The main frontend is the Livewire components and views. Ignore (but do not remove) Vue and Inertia, they are vestigial previous versions waiting to be converted to Livewire.
- **Naming:** 
    - Controllers: `PascalCaseController`
    - Models: `PascalCase` (singular)
    - Migrations: `yyyy_mm_dd_hhmmss_description.php`
    - Components: `PascalCase.php`

### AI Collaboration Best Practices
1. **Specific Preferences:** Prefer the Livewire frontend components to be Volt single file components whenever possible.
2. **Commit Messages:** Do not propose commits without my approval and explicit instruction.
3. **Documentation:** A PHP DocBlock is required for every public controller endpoint method.
4. **Testing Strategy:** All testing must be performed inside running containers via Laravel Sail. Additionally, ParaTest has been installed, so parallel testing is available and should be utilized for optimum speed. To maintain compatibility with ParaTest, avoid using the RefreshDatabase trait, opting instead for the DatabaseTransactions trait. Tests must be written that cover at least 85% of lines in the main project files. When testing Filament components, extend the tests/Feature/Filament/Parent test files where appropriate, use the disabling traits in that same directory where appropriate, and add additional tests to achieve full coverage.

[//]: # (5. **Architectural Decisions:** Note any project-specific architectural patterns &#40;e.g., "Use Service classes for complex business logic"&#41;.)

### How to update this file
- Add new guidelines as the project evolves.
- Keep instructions concise and actionable.
- Group related guidelines under appropriate headers.
