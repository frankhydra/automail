<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Organization;
use App\Models\User;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Content Studio: the organization's image library.
 *
 * Safety choices:
 *  - Only real raster images (jpg, png, gif, webp) are accepted, checked by file
 *    content rather than the name. SVG is refused on purpose: it can carry scripts
 *    and most email apps do not display it anyway.
 *  - Files are stored under a random name, never the uploaded one.
 *  - Every query is scoped to the current organization.
 */
class AssetController extends Controller
{
    use EnsuresTeamPermission;

    /** Total image storage allowed per organization, in bytes (50 MB). */
    public const STORAGE_CAP = 52428800;

    protected function organization(): Organization
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        abort_if($organization === null, 403, 'No active organization found for your account.');

        return $organization;
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $organization = $this->organization();

        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        $localOnly = in_array($host, ['localhost', '127.0.0.1', '::1'], true) || str_ends_with($host, '.test') || str_ends_with($host, '.local');

        return view('assets.index', [
            'assets' => $organization->assets()->latest()->get(),
            'usedBytes' => (int) $organization->assets()->sum('size'),
            'capBytes' => self::STORAGE_CAP,
            'canUpload' => in_array($user->currentRole(), ['owner', 'admin', 'manager', 'editor'], true),
            'canDelete' => in_array($user->currentRole(), ['owner', 'admin'], true),
            'localOnly' => $localOnly,
        ]);
    }

    /**
     * JSON list used by the Email Builder's "Choose from Content Studio" picker.
     */
    public function list(): JsonResponse
    {
        $assets = $this->organization()->assets()->latest()->take(100)->get()
            ->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'url' => $asset->url(),
                'width' => $asset->width,
                'height' => $asset->height,
            ]);

        return response()->json(['assets' => $assets]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $this->organization();

        $request->validate([
            'file' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp',
                'max:5120', // 5 MB per image
                'dimensions:max_width=6000,max_height=6000',
            ],
        ], [
            'file.image' => 'Only JPG, PNG, GIF or WebP images can be uploaded.',
            'file.mimes' => 'Only JPG, PNG, GIF or WebP images can be uploaded.',
            'file.max' => 'Images can be at most 5 MB.',
            'file.dimensions' => 'Images can be at most 6000 x 6000 pixels.',
        ]);

        $file = $request->file('file');

        $used = (int) $organization->assets()->sum('size');
        if ($used + $file->getSize() > self::STORAGE_CAP) {
            return $this->fail($request, 'You have used all of your image storage (50 MB). Delete some images first.');
        }

        $dimensions = @getimagesize($file->getRealPath()) ?: [null, null];

        // store() picks a random file name and a safe extension from the real mime type.
        $path = $file->store("assets/{$organization->id}", 'public');

        $asset = $organization->assets()->create([
            'uploaded_by' => $user->id,
            'name' => $this->cleanName($file->getClientOriginalName()),
            'path' => $path,
            'mime_type' => (string) $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $dimensions[0],
            'height' => $dimensions[1],
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Image uploaded.',
                'asset' => ['id' => $asset->id, 'name' => $asset->name, 'url' => $asset->url(), 'width' => $asset->width, 'height' => $asset->height],
            ], 201);
        }

        return redirect()->route('assets.index')->with('status', 'Image uploaded successfully!');
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $this->organization();

        $asset = $organization->assets()->find($id);

        if (!$asset) {
            return redirect()->route('assets.index')->withErrors(['error' => 'Image not found.']);
        }

        Storage::disk('public')->delete($asset->path);
        $asset->delete();

        return redirect()->route('assets.index')->with('status', 'Image deleted.');
    }

    /** Display name only: drop any folder part, control characters and excess length. */
    protected function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return mb_substr(trim($name) !== '' ? trim($name) : 'image', 0, 120);
    }

    protected function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => $message], 422);
        }

        return redirect()->route('assets.index')->withErrors(['file' => $message]);
    }
}
