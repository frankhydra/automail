<?php

namespace App\Services;

/**
 * Compiles the visual builder's block list into a single HTML email body.
 * This is the ONE place blocks turn into markup - both the "Save" action, the
 * builder's live "Preview" and tests call this, so the builder UI and the
 * actual sent email can never drift apart. Table-based layout with inline
 * styles throughout: deliberate, because most email clients (Outlook
 * especially) ignore flexbox/grid.
 */
class TemplateBlockRenderer
{
    /**
     * @param list<array<string, mixed>> $blocks
     * @param string|null $previewText Inbox snippet; emitted as a hidden preheader.
     */
    public static function render(array $blocks, ?string $previewText = null): string
    {
        $rows = '';

        foreach ($blocks as $block) {
            $type = $block['type'] ?? null;
            $rows .= match ($type) {
                'header' => self::header($block),
                'text' => self::text($block),
                'image' => self::image($block),
                'button' => self::button($block),
                'divider' => self::divider(),
                'spacer' => self::spacer($block),
                'columns' => self::columns($block),
                'social' => self::social($block),
                'footer' => self::footer($block),
                // An unrecognized block type is skipped rather than breaking the
                // whole email - the rest of the template still renders.
                default => '',
            };
        }

        $preheader = '';
        $previewText = trim((string) $previewText);
        if ($previewText !== '') {
            $preheader = '<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;mso-hide:all;">'.e($previewText).'</div>';
        }

        return <<<HTML
        {$preheader}
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:24px 0;">
            <tr>
                <td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;font-family:Arial,Helvetica,sans-serif;">
                        {$rows}
                    </table>
                </td>
            </tr>
        </table>
        HTML;
    }

    /**
     * The list of block types the builder UI and validation both recognize.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return ['header', 'text', 'image', 'button', 'divider', 'spacer', 'columns', 'social', 'footer'];
    }

    protected static function header(array $block): string
    {
        $title = e($block['title'] ?? '');
        $subtitle = e($block['subtitle'] ?? '');
        $align = self::align($block, 'center');

        return <<<HTML
        <tr>
            <td style="padding:32px 24px 16px 24px;text-align:{$align};">
                <h1 style="margin:0;font-size:24px;color:#111827;">{$title}</h1>
                <p style="margin:8px 0 0 0;font-size:14px;color:#6b7280;">{$subtitle}</p>
            </td>
        </tr>
        HTML;
    }

    protected static function text(array $block): string
    {
        $content = nl2br(e($block['content'] ?? ''));
        $size = match ($block['font_size'] ?? 'medium') {
            'small' => 13,
            'large' => 18,
            default => 15,
        };
        $align = self::align($block, 'left');

        return <<<HTML
        <tr>
            <td style="padding:8px 24px;font-size:{$size}px;line-height:1.6;color:#374151;text-align:{$align};">{$content}</td>
        </tr>
        HTML;
    }

    protected static function image(array $block): string
    {
        $rawUrl = trim((string) ($block['url'] ?? ''));

        // An image block with no address yet would render a broken picture in the
        // sent email, so it is skipped entirely until a URL is filled in.
        if ($rawUrl === '') {
            return '';
        }

        $url = e($rawUrl);
        $alt = e($block['alt'] ?? '');
        $link = self::safeUrl($block['link'] ?? '', '');

        $img = '<img src="'.$url.'" alt="'.$alt.'" width="552" style="display:block;width:100%;max-width:552px;height:auto;border:0;">';

        if ($link !== '') {
            $img = '<a href="'.e($link).'" target="_blank">'.$img.'</a>';
        }

        return <<<HTML
        <tr>
            <td style="padding:8px 24px;">{$img}</td>
        </tr>
        HTML;
    }

    protected static function button(array $block): string
    {
        $label = e($block['label'] ?? 'Click here');
        $url = e(self::safeUrl($block['url'] ?? '#', '#'));
        $color = self::safeHexColor($block['color'] ?? '#4f46e5');
        $align = self::align($block, 'center');
        $margin = match ($align) {
            'left' => '0 auto 0 0',
            'right' => '0 0 0 auto',
            default => '0 auto',
        };

        return <<<HTML
        <tr>
            <td style="padding:16px 24px;text-align:{$align};">
                <table role="presentation" cellpadding="0" cellspacing="0" style="margin:{$margin};">
                    <tr>
                        <td style="background-color:{$color};border-radius:6px;">
                            <a href="{$url}" target="_blank" style="display:inline-block;padding:12px 24px;font-size:14px;font-weight:bold;color:#ffffff;text-decoration:none;">{$label}</a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        HTML;
    }

    protected static function divider(): string
    {
        return <<<HTML
        <tr>
            <td style="padding:8px 24px;">
                <hr style="border:none;border-top:1px solid #e5e7eb;margin:0;">
            </td>
        </tr>
        HTML;
    }

    protected static function spacer(array $block): string
    {
        $height = max(4, min(120, (int) ($block['height'] ?? 24)));

        return <<<HTML
        <tr>
            <td style="height:{$height}px;line-height:{$height}px;font-size:0;">&nbsp;</td>
        </tr>
        HTML;
    }

    protected static function columns(array $block): string
    {
        $left = nl2br(e($block['left'] ?? ''));
        $right = nl2br(e($block['right'] ?? ''));

        return <<<HTML
        <tr>
            <td style="padding:8px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" valign="top" style="padding-right:12px;font-size:14px;line-height:1.6;color:#374151;">{$left}</td>
                        <td width="50%" valign="top" style="padding-left:12px;font-size:14px;line-height:1.6;color:#374151;">{$right}</td>
                    </tr>
                </table>
            </td>
        </tr>
        HTML;
    }

    protected static function social(array $block): string
    {
        $networks = [
            'twitter' => 'Twitter',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'linkedin' => 'LinkedIn',
        ];

        $links = '';
        foreach ($networks as $key => $label) {
            $url = self::safeUrl($block[$key] ?? '', '');
            if ($url === '') {
                continue;
            }
            $links .= '<a href="'.e($url).'" target="_blank" style="display:inline-block;margin:0 10px;font-size:13px;font-weight:bold;color:#6b7280;text-decoration:none;">'.$label.'</a>';
        }

        // No links filled in yet: nothing to show, so the row is left out.
        if ($links === '') {
            return '';
        }

        return <<<HTML
        <tr>
            <td style="padding:12px 24px;text-align:center;">{$links}</td>
        </tr>
        HTML;
    }

    protected static function footer(array $block): string
    {
        $text = e($block['text'] ?? '');
        $social = e($block['social_text'] ?? '');

        return <<<HTML
        <tr>
            <td style="padding:24px;text-align:center;font-size:12px;color:#9ca3af;border-top:1px solid #e5e7eb;">
                <p style="margin:0 0 4px 0;">{$text}</p>
                <p style="margin:0;">{$social}</p>
            </td>
        </tr>
        HTML;
    }

    /** Only left / center / right are accepted, so the value can't break out of the style attribute. */
    protected static function align(array $block, string $default): string
    {
        $value = (string) ($block['align'] ?? $default);

        return in_array($value, ['left', 'center', 'right'], true) ? $value : $default;
    }

    /**
     * Links may only be http(s), mailto, tel or "#". Anything else (notably
     * "javascript:") is replaced with the fallback.
     */
    protected static function safeUrl(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        if ($value === '#' || preg_match('#^(https?://|mailto:|tel:)#i', $value)) {
            return $value;
        }

        return $fallback;
    }

    /**
     * Only accept a plain #rrggbb/#rgb value for a block's color field, so it
     * can never be used to break out of the inline style attribute.
     */
    protected static function safeHexColor(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^#[0-9a-fA-F]{3,6}$/', $value) ? $value : '#4f46e5';
    }
}
