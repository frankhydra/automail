<?php

namespace App\Services;

use App\Models\Contact;

class TemplateRendererService
{
    /**
     * Parse and render template subject or content by replacing variables.
     *
     * @param string $templateText
     * @param Contact|array|null $data
     * @return string
     */
    public function render(string $templateText, $data = null): string
    {
        if (empty($templateText)) {
            return '';
        }

        // Extract values from Contact model or array
        $firstName = '';
        $lastName = '';
        $email = '';

        if ($data instanceof Contact) {
            $firstName = $data->first_name ?? '';
            $lastName = $data->last_name ?? '';
            $email = $data->email ?? '';
        } elseif (is_array($data)) {
            $firstName = $data['first_name'] ?? ($data['first_name'] ?? '');
            $lastName = $data['last_name'] ?? ($data['last_name'] ?? '');
            $email = $data['email'] ?? ($data['email'] ?? '');
        }

        // Variable Replacements
        $replacements = [
            '{{first_name}}' => $firstName ?: 'Valued Customer',
            '{{last_name}}' => $lastName,
            '{{email}}' => $email,
            '{{ name }}' => trim("{$firstName} {$lastName}") ?: 'Valued Customer',
            '{{ first_name }}' => $firstName ?: 'Valued Customer',
            '{{ last_name }}' => $lastName,
            '{{ email }}' => $email,
        ];

        return strtr($templateText, $replacements);
    }
}