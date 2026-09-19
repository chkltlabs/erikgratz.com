<?php

declare(strict_types=1);

namespace App\Content;

class WorkHistory
{
    /**
     * @return list<array{
     *     company: string,
     *     location: string,
     *     title: string,
     *     timeframe: string,
     *     bullets: list<string>,
     *     technologies: list<string>
     * }>
     */
    public function roles(): array
    {
        /** @var list<array{company: string, location: string, title: string, timeframe: string, bullets: list<string>, technologies: list<string>}> $roles */
        $roles = config('work-history', []);

        return array_map(static function (array $role): array {
            $role['timeframe'] = str_replace('{year}', (string) now()->year, $role['timeframe']);

            return $role;
        }, $roles);
    }

    /**
     * @return list<string>
     */
    public function companyNames(): array
    {
        return array_values(array_unique(array_map(
            static fn (array $role): string => $role['company'],
            $this->roles(),
        )));
    }
}
