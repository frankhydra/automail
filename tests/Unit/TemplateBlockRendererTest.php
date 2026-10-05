<?php

namespace Tests\Unit;

use App\Services\TemplateBlockRenderer;
use Tests\TestCase;

class TemplateBlockRendererTest extends TestCase
{
    public function test_renders_a_header_block(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'header', 'title' => 'Hello World', 'subtitle' => 'A subtitle'],
        ]);

        $this->assertStringContainsString('Hello World', $html);
        $this->assertStringContainsString('A subtitle', $html);
    }

    public function test_escapes_text_content_to_prevent_xss(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'text', 'content' => '<script>alert(1)</script>'],
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_renders_a_button_with_a_safe_color(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'button', 'label' => 'Buy Now', 'url' => 'https://example.com', 'color' => '#ff0000'],
        ]);

        $this->assertStringContainsString('Buy Now', $html);
        $this->assertStringContainsString('#ff0000', $html);
        $this->assertStringContainsString('https://example.com', $html);
    }

    public function test_rejects_an_unsafe_color_value_and_falls_back_to_default(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'button', 'label' => 'Go', 'url' => '#', 'color' => 'red;"></td></tr></table><script>alert(1)</script>'],
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('#4f46e5', $html); // fallback color
    }

    public function test_an_unknown_block_type_is_skipped_without_error(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'header', 'title' => 'Kept'],
            ['type' => 'not-a-real-type', 'title' => 'Dropped'],
        ]);

        $this->assertStringContainsString('Kept', $html);
        $this->assertStringNotContainsString('Dropped', $html);
    }

    public function test_columns_block_renders_both_sides(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'columns', 'left' => 'Left side', 'right' => 'Right side'],
        ]);

        $this->assertStringContainsString('Left side', $html);
        $this->assertStringContainsString('Right side', $html);
    }

    public function test_divider_and_footer_render_without_errors(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'divider'],
            ['type' => 'footer', 'text' => 'Company Address', 'social_text' => 'Follow us'],
        ]);

        $this->assertStringContainsString('<hr', $html);
        $this->assertStringContainsString('Company Address', $html);
        $this->assertStringContainsString('Follow us', $html);
    }

    public function test_types_lists_every_supported_block(): void
    {
        $this->assertSame(
            ['header', 'text', 'image', 'button', 'divider', 'spacer', 'columns', 'social', 'footer'],
            TemplateBlockRenderer::types()
        );
    }

    public function test_preview_text_is_added_as_a_hidden_preheader(): void
    {
        $html = TemplateBlockRenderer::render([['type' => 'text', 'content' => 'Body']], 'Inbox snippet');

        $this->assertStringContainsString('display:none', $html);
        $this->assertStringContainsString('Inbox snippet', $html);
    }

    public function test_javascript_links_are_neutralised(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'button', 'label' => 'Go', 'url' => 'javascript:alert(1)', 'color' => '#ff0000'],
            ['type' => 'image', 'url' => 'https://example.com/a.png', 'link' => 'javascript:alert(2)'],
        ]);

        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_an_image_without_a_url_and_empty_social_links_render_nothing(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'image', 'url' => ''],
            ['type' => 'social'],
        ]);

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('Twitter', $html);
    }

    public function test_social_and_spacer_blocks_render(): void
    {
        $html = TemplateBlockRenderer::render([
            ['type' => 'social', 'twitter' => 'https://twitter.com/acme'],
            ['type' => 'spacer', 'height' => '40'],
        ]);

        $this->assertStringContainsString('https://twitter.com/acme', $html);
        $this->assertStringContainsString('height:40px', $html);
    }
}
