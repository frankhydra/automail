<?php

namespace Tests\Feature;

use App\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesTenants;
use Tests\TestCase;

class ContentStudioTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** A real 1x1 PNG, so the tests do not need the GD extension. */
    protected function png(string $name = 'logo.png'): UploadedFile
    {
        $bytes = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_the_page_loads(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->get(route('assets.index'))->assertOk()->assertSee('Content Studio');
    }

    public function test_a_png_can_be_uploaded_and_is_stored_under_a_random_name(): void
    {
        [$user, $org] = $this->makeTenant();

        $this->actingAs($user)->post(route('assets.store'), ['file' => $this->png('My Logo.png')])
            ->assertSessionHasNoErrors();

        $asset = $org->assets()->first();
        $this->assertSame('My Logo.png', $asset->name);
        $this->assertStringStartsWith("assets/{$org->id}/", $asset->path);
        $this->assertStringNotContainsString('My Logo', $asset->path);
        $this->assertSame(1, $asset->width);
        Storage::disk('public')->assertExists($asset->path);
    }

    public function test_the_builder_picker_can_upload_through_json(): void
    {
        [$user] = $this->makeTenant();

        $this->actingAs($user)->postJson(route('assets.store'), ['file' => $this->png()])
            ->assertCreated()->assertJsonStructure(['asset' => ['id', 'name', 'url']]);
    }

    public function test_non_images_and_svgs_are_refused(): void
    {
        [$user, $org] = $this->makeTenant();

        $php = UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;');
        $svg = UploadedFile::fake()->createWithContent('icon.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');
        $disguised = UploadedFile::fake()->createWithContent('photo.png', '<?php echo 1;');

        foreach ([$php, $svg, $disguised] as $file) {
            $this->actingAs($user)->post(route('assets.store'), ['file' => $file])->assertSessionHasErrors('file');
        }

        $this->assertSame(0, $org->assets()->count());
    }

    public function test_a_viewer_cannot_upload_or_delete(): void
    {
        [$owner, $org] = $this->makeTenant();
        $viewer = $this->attachMember($org, 'viewer');

        $this->actingAs($viewer)->post(route('assets.store'), ['file' => $this->png()])->assertForbidden();

        $this->actingAs($owner)->post(route('assets.store'), ['file' => $this->png()]);
        $asset = $org->assets()->first();

        $this->actingAs($viewer)->delete(route('assets.destroy', $asset->id))->assertForbidden();
        $this->assertSame(1, $org->assets()->count());
    }

    public function test_delete_removes_the_file_and_the_row(): void
    {
        [$user, $org] = $this->makeTenant();
        $this->actingAs($user)->post(route('assets.store'), ['file' => $this->png()]);
        $asset = $org->assets()->first();

        $this->actingAs($user)->delete(route('assets.destroy', $asset->id))->assertRedirect(route('assets.index'));

        $this->assertSame(0, $org->assets()->count());
        Storage::disk('public')->assertMissing($asset->path);
    }

    public function test_one_organization_cannot_see_or_delete_anothers_assets(): void
    {
        [$user, $org] = $this->makeTenant('Acme');
        [$otherUser, $other] = $this->makeTenant('Other');

        $this->actingAs($otherUser)->post(route('assets.store'), ['file' => $this->png('secret-plan.png')]);
        $theirs = $other->assets()->first();

        $this->actingAs($user)->get(route('assets.index'))->assertOk()->assertDontSee('secret-plan');
        $this->actingAs($user)->getJson(route('assets.list'))->assertOk()->assertJsonCount(0, 'assets');

        $this->actingAs($user)->delete(route('assets.destroy', $theirs->id));
        $this->assertSame(1, Asset::where('id', $theirs->id)->count());
        Storage::disk('public')->assertExists($theirs->path);
    }

    public function test_the_storage_cap_is_enforced(): void
    {
        [$user, $org] = $this->makeTenant();
        $org->assets()->create([
            'name' => 'huge.png', 'path' => 'assets/x/huge.png', 'mime_type' => 'image/png',
            'size' => \App\Http\Controllers\AssetController::STORAGE_CAP,
        ]);

        $this->actingAs($user)->postJson(route('assets.store'), ['file' => $this->png()])->assertStatus(422);
    }
}
