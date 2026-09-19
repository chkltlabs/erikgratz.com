<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class LocalCardsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $owners = $this->owners();

        foreach ($this->cards() as $card) {
            $owner = $card['owner'];
            unset($card['owner']);

            Card::query()->updateOrCreate(
                ['name' => $card['name']],
                [
                    ...$card,
                    'user_id' => $owners[$owner]->id,
                ],
            );
        }
    }

    /**
     * @return array{erik: User, amy: User}
     */
    private function owners(): array
    {
        return [
            'erik' => User::query()->firstOrCreate(
                ['email' => 'erik@erikgratz.com'],
                [
                    'name' => 'Erik G',
                    'password' => Hash::make('password'),
                ],
            ),
            'amy' => User::query()->firstOrCreate(
                ['email' => 'hudgins.a8@gmail.com'],
                [
                    'name' => 'Amy G',
                    'password' => Hash::make('password'),
                ],
            ),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cards(): array
    {
        return json_decode(File::get(database_path('data/local-cards.json')), true, 512, JSON_THROW_ON_ERROR);
    }
}
