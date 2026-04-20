<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Contacts\Pages\ManageContacts;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Filament\FilamentTestCase;

class ContactTest extends FilamentTestCase
{
    use RefreshDatabase;

    #[Test]
    public function resource_index_route_renders(): void
    {
        $this->get(ContactResource::getUrl('index'))->assertSuccessful();
    }

    #[Test]
    public function resource_page_registration_includes_index(): void
    {
        $pages = ContactResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertInstanceOf(\Filament\Resources\Pages\PageRegistration::class, $pages['index']);
    }

    #[Test]
    public function table_shows_expected_columns_and_default_sort_order(): void
    {
        $older = Contact::factory()->create([
            'name' => 'Older Contact',
            'contact' => 'older@example.com',
            'message' => 'Older contact message body to be abbreviated.',
            'created_at' => now()->subDay(),
        ]);

        $newer = Contact::factory()->create([
            'name' => 'Newer Contact',
            'contact' => 'newer@example.com',
            'message' => 'Newer contact message body to be abbreviated.',
            'created_at' => now(),
        ]);

        Livewire::test(ManageContacts::class)
            ->set('tableRecordsPerPage', 'all')
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
            ->assertSee('Email / phone')
            ->assertSee('Message');
    }

    #[Test]
    public function table_row_actions_and_bulk_delete_work(): void
    {
        $first = Contact::factory()->create();
        $second = Contact::factory()->create();

        Livewire::test(ManageContacts::class)
            ->callTableAction('view', $first)
            ->assertHasNoTableActionErrors()
            ->callTableBulkAction('delete', [$first, $second])
            ->assertHasNoTableActionErrors();

        $this->assertModelMissing($first);
        $this->assertModelMissing($second);
    }

    #[Test]
    public function table_has_expected_record_actions(): void
    {
        Contact::factory()->create();

        Livewire::test(ManageContacts::class)
            ->assertTableActionExists('view')
            ->assertTableActionExists('delete')
            ->assertTableBulkActionExists('delete');
    }

    #[Test]
    public function manage_contacts_has_no_header_actions(): void
    {
        $component = new class extends ManageContacts
        {
            public function exposedHeaderActions(): array
            {
                return $this->getHeaderActions();
            }
        };

        $this->assertSame([], $component->exposedHeaderActions());
    }
}
