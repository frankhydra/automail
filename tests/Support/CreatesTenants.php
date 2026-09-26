<?php

namespace Tests\Support;

use App\Contracts\EmailProviderInterface;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\SendingIdentity;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesTenants
{
    /**
     * @return array{0: User, 1: Organization}
     */
    protected function makeTenant(string $name = 'Acme'): array
    {
        $user = User::factory()->create();

        $organization = Organization::create([
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(6),
        ]);

        $user->organizations()->attach($organization->id, ['role' => 'owner']);

        return [$user, $organization];
    }

    protected function makeIdentity(Organization $organization, bool $verified = true, array $attributes = []): SendingIdentity
    {
        return $organization->sendingIdentities()->create(array_merge([
            'from_name' => 'Sender',
            'from_email' => fake()->unique()->safeEmail(),
            'type' => 'personal',
            'verification_status' => $verified ? 'verified' : 'pending',
            'verification_token' => Str::random(40),
            'verified_at' => $verified ? now() : null,
        ], $attributes));
    }

    protected function makeContact(Organization $organization, array $attributes = []): Contact
    {
        return $organization->contacts()->create(array_merge([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => fake()->unique()->safeEmail(),
            'status' => 'subscribed',
        ], $attributes));
    }

    /**
     * @param list<Contact> $contacts
     */
    protected function makeCampaign(Organization $organization, SendingIdentity $identity, array $contacts, array $attributes = []): Campaign
    {
        $campaign = $organization->campaigns()->create(array_merge([
            'sending_identity_id' => $identity->id,
            'name' => 'Test campaign',
            'subject' => 'Hello {{first_name}}',
            'body' => '<p>Hi {{first_name}}</p>',
            'status' => 'draft',
        ], $attributes));

        foreach ($contacts as $contact) {
            $campaign->recipients()->create(['contact_id' => $contact->id, 'status' => 'pending']);
        }

        return $campaign;
    }

    /**
     * Create a second user attached to an existing organization with the given role
     * (e.g. "editor", "viewer") - anything other than "owner".
     */
    protected function attachMember(Organization $organization, string $role): User
    {
        $user = User::factory()->create();
        $organization->users()->attach($user->id, ['role' => $role]);

        return $user;
    }

    protected function useFakeProvider(bool $succeed = true): FakeEmailProvider
    {
        $provider = new FakeEmailProvider($succeed);
        $this->app->instance(EmailProviderInterface::class, $provider);

        return $provider;
    }
}
