<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TemplateBlockRenderer;
use App\Services\TemplateRendererService;
use App\Support\EnsuresTeamPermission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TemplateController extends Controller
{
    use EnsuresTeamPermission;

    protected TemplateRendererService $rendererService;

    public function __construct(TemplateRendererService $rendererService)
    {
        $this->rendererService = $rendererService;
    }

    public function index(): View
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $templates = $organization ? $organization->templates()->latest()->get() : collect();

        return view('templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('templates.create', ['blockTypes' => TemplateBlockRenderer::types()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            // Exactly one of these arrives, depending on which editor mode was active.
            'blocks_json' => 'nullable|json',
            'body' => 'required_without:blocks_json|nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        if (!$organization) {
            return redirect()->back()->withErrors(['error' => 'No active organization found.']);
        }

        $content = $this->resolveContent($request);

        $organization->templates()->create([
            'name' => trim($request->input('name')),
            'subject' => $request->filled('subject') ? trim($request->input('subject')) : null,
            'body' => $content['body'],
            'blocks' => $content['blocks'],
        ]);

        return redirect()->route('templates.index')->with('status', 'Template created successfully!');
    }

    public function edit(int $id): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $organization = $user ? $user->currentOrganization() : null;

        $template = $organization ? $organization->templates()->find($id) : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        // Preview with sample data. The subject is plain text (not HTML-escaped by the renderer).
        $sampleData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $renderedSubject = $this->rendererService->render((string) $template->subject, $sampleData, false);
        $renderedBody = $this->rendererService->render((string) $template->body, $sampleData);

        return view('templates.edit', [
            'template' => $template,
            'renderedSubject' => $renderedSubject,
            'renderedBody' => $renderedBody,
            'blockTypes' => TemplateBlockRenderer::types(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'blocks_json' => 'nullable|json',
            'body' => 'required_without:blocks_json|nullable|string',
        ]);

        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanEditCampaigns($user);
        $organization = $user->currentOrganization();

        $template = $organization ? $organization->templates()->find($id) : null;

        if (!$template) {
            return redirect()->route('templates.index')->withErrors(['error' => 'Template not found.']);
        }

        $content = $this->resolveContent($request);

        $template->update([
            'name' => trim($request->input('name')),
            'subject' => $request->filled('subject') ? trim($request->input('subject')) : null,
            'body' => $content['body'],
            'blocks' => $content['blocks'],
        ]);

        return redirect()->route('templates.index')->with('status', 'Template updated successfully!');
    }

    public function destroy(int $id): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        $this->ensureCanManage($user);
        $organization = $user->currentOrganization();

        if ($organization) {
            $organization->templates()->where('id', $id)->delete();
        }

        return redirect()->route('templates.index')->with('status', 'Template deleted successfully.');
    }

    /**
     * If the visual builder was used (blocks_json present and valid), compile
     * it into HTML with TemplateBlockRenderer - this is the single place
     * blocks become the sendable "body", so the builder and the actual email
     * can never drift apart. Otherwise falls back to the raw HTML editor's
     * "body" field, exactly as before this milestone, and blocks stays null.
     *
     * @return array{body: string, blocks: ?array}
     */
    protected function resolveContent(Request $request): array
    {
        $rawBlocks = $request->input('blocks_json');

        if ($rawBlocks) {
            $decoded = json_decode($rawBlocks, true);
            $blocks = $this->sanitizeBlocks(is_array($decoded) ? $decoded : []);

            return ['body' => TemplateBlockRenderer::render($blocks), 'blocks' => $blocks];
        }

        return ['body' => (string) $request->input('body', ''), 'blocks' => null];
    }

    /**
     * Keep only blocks with a type TemplateBlockRenderer understands, and cast
     * every field to a length-capped string - the renderer itself escapes all
     * output, so this is a sanity check, not the security boundary.
     *
     * @return list<array<string, mixed>>
     */
    protected function sanitizeBlocks(array $blocks): array
    {
        $allowedTypes = TemplateBlockRenderer::types();
        $clean = [];

        foreach ($blocks as $block) {
            if (!is_array($block) || !in_array($block['type'] ?? null, $allowedTypes, true)) {
                continue;
            }

            $fields = ['type' => $block['type']];
            foreach ($block as $key => $value) {
                if ($key === 'type') {
                    continue;
                }
                $fields[$key] = mb_substr((string) $value, 0, 2000);
            }

            $clean[] = $fields;
        }

        return $clean;
    }
}
