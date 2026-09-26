<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class CampaignTenancyTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Launch',
            'subject' => 'Hello',
            'body' => '<p>Body</p>',
        ], $override);
    }

    public function test_campaign_rejects_another_organizations_template(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $identity = $this->makeIdentity($orgA);
        $this->makeContact($orgA);
        $foreignTemplate = $orgB->templates()->create(['name' => 'x', 'subject' => 's', 'body' => 'b']);

        $this->actingAs($userA)
            ->post(route('campaigns.store'), $this->payload([
                'sending_identity_id' => $identity->id,
                'template_id' => $foreignTemplate->id,
            ]))
            ->assertSessionHasErrors('template_id');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_rejects_another_organizations_sending_identity(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $this->makeContact($orgA);
        $foreignIdentity = $this->makeIdentity($orgB);

        $this->actingAs($userA)
            ->post(route('campaigns.store'), $this->payload(['sending_identity_id' => $foreignIdentity->id]))
            ->assertSessionHasErrors('sending_identity_id');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_rejects_another_organizations_contact_list(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $identity = $this->makeIdentity($orgA);
        $this->makeContact($orgA);
        $foreignList = $orgB->contactLists()->create(['name' => 'B list']);

        $this->actingAs($userA)
            ->post(route('campaigns.store'), $this->payload([
                'sending_identity_id' => $identity->id,
                'contact_list_id' => $foreignList->id,
            ]))
            ->assertSessionHasErrors('contact_list_id');

        $this->assertDatabaseCount('campaigns', 0);
    }

    public function test_campaign_can_be_created_with_own_records(): void
    {
        [$userA, $orgA] = $this->makeTenant('A');

        $identity = $this->makeIdentity($orgA);
        $this->makeContact($orgA);
        $template = $orgA->templates()->create(['name' => 'x', 'subject' => 's', 'body' => 'b']);

        $this->actingAs($userA)
            ->post(route('campaigns.store'), $this->payload([
                'sending_identity_id' => $identity->id,
                'template_id' => $template->id,
            ]))
            ->assertRedirect(route('campaigns.index'));

        $this->assertDatabaseCount('campaigns', 1);
        $this->assertDatabaseCount('campaign_recipients', 1);
    }

    public function test_user_cannot_view_or_dispatch_another_organizations_campaign(): void
    {
        [$userA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $identityB = $this->makeIdentity($orgB);
        $contactB = $this->makeContact($orgB);
        $campaignB = $this->makeCampaign($orgB, $identityB, [$contactB]);

        $this->actingAs($userA)
            ->get(route('campaigns.show', $campaignB->id))
            ->assertRedirect(route('campaigns.index'));

        $this->actingAs($userA)
            ->post(route('campaigns.dispatch', $campaignB->id))
            ->assertRedirect(route('campaigns.index'));

        $this->assertSame('draft', $campaignB->fresh()->status);
    }

    public function test_csv_import_rejects_another_organizations_list(): void
    {
        [$userA] = $this->makeTenant('A');
        [, $orgB] = $this->makeTenant('B');

        $foreignList = $orgB->contactLists()->create(['name' => 'B list']);
        $file = UploadedFile::fake()->createWithContent('contacts.csv', "email\nnew@example.com\n");

        $this->actingAs($userA)
            ->post(route('contacts.import.store'), [
                'csv_file' => $file,
                'contact_list_id' => $foreignList->id,
            ])
            ->assertSessionHasErrors('contact_list_id');
    }
}
