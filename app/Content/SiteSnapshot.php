<?php

declare(strict_types=1);

namespace App\Content;

use App\Models\Photo;

class SiteSnapshot
{
    public function __construct(
        protected WorkHistory $workHistory,
    ) {}

    /**
     * Public site facts the terminal renders. Same sources as the website.
     *
     * @return array{
     *     identity: array{name: string, headline: string, sub: string},
     *     experience: list<array{company: string, location: string, title: string, timeframe: string, bullets: list<string>, technologies: list<string>}>,
     *     portfolio: list<array{title: string, text: string, link: string|null, links: list<array{text: string, href: string}>}>,
     *     photos: list<array{title: string, description: string, tags: list<string>, url: string|null}>,
     *     contact: array{email: string, phone: string, resume_url: string}
     * }
     */
    public function toArray(): array
    {
        return [
            'identity' => [
                'name' => 'Erik V Gratz',
                'headline' => '',
                'sub' => 'I have a passion for computers. I create solutions to modern web problems.',
            ],
            'experience' => $this->workHistory->roles(),
            'portfolio' => array_map(fn (array $item): array => $this->portfolioItem($item), config('portfolio', [])),
            'photos' => Photo::query()->orderBy('id')->get()->map(fn (Photo $photo): array => [
                'title' => (string) $photo->title,
                'description' => (string) ($photo->description ?? ''),
                'tags' => array_values($photo->tags ?? []),
                'url' => $this->publicUrl($photo->url),
            ])->all(),
            'contact' => [
                'email' => (string) config('contact.email'),
                'phone' => (string) config('contact.phone'),
                'resume_url' => (string) config('contact.resumeUrl'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array{title: string, text: string, link: string|null, links: list<array{text: string, href: string}>}
     */
    private function portfolioItem(array $item): array
    {
        $links = [];
        $primary = isset($item['link']) ? (string) $item['link'] : '';
        if ($primary !== '') {
            $text = isset($item['linkText']) ? (string) $item['linkText'] : '';
            $links[] = ['text' => $text !== '' ? $text : 'Open', 'href' => $primary];
        }
        foreach ($item['extraLinks'] ?? [] as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $href = (string) ($extra['href'] ?? '');
            if ($href === '') {
                continue;
            }
            $text = (string) ($extra['text'] ?? '');
            $links[] = ['text' => $text !== '' ? $text : 'Open', 'href' => $href];
        }

        return [
            'title' => (string) ($item['title'] ?? ''),
            'text' => (string) ($item['text'] ?? ''),
            'link' => $primary !== '' ? $primary : null,
            'links' => $links,
        ];
    }

    private function publicUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return url($url);
    }
}
