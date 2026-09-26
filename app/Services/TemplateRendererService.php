<?php

namespace App\Services;

use App\Models\Contact;

class TemplateRendererService
{
    /**
     * Replace {{variables}} in a template subject or body.
     *
     * @param string             $templateText
     * @param Contact|array|null $data
     * @param bool               $escape Escape substituted values for HTML. Use true (default)
     *                                   for HTML bodies so contact data can never inject markup;
     *                                   use false for plain-text fields such as the subject line.
     */
    public function render(string $templateText, $data = null, bool $escape = true): string
    {
        if ($templateText === '') {
            return '';
        }

        $firstName = '';
        $lastName = '';
        $email = '';

        if ($data instanceof Contact) {
            $firstName = (string) ($data->first_name ?? '');
            $lastName = (string) ($data->last_name ?? '');
            $email = (string) ($data->email ?? '');
        } elseif (is_array($data)) {
            $firstName = (string) ($data['first_name'] ?? '');
            $lastName = (string) ($data['last_name'] ?? '');
            $email = (string) ($data['email'] ?? '');
        }

        $fullName = trim("{$firstName} {$lastName}");

        $values = [
            'first_name' => $firstName !== '' ? $firstName : 'Valued Customer',
            'last_name' => $lastName,
            'email' => $email,
            'name' => $fullName !== '' ? $fullName : 'Valued Customer',
        ];

        $replacements = [];
        foreach ($values as $key => $value) {
            $value = $escape ? e($value) : $value;
            $replacements['{{'.$key.'}}'] = $value;
            $replacements['{{ '.$key.' }}'] = $value;
        }

        // strtr does not re-scan replaced text, so a value containing "{{email}}" stays literal.
        return strtr($templateText, $replacements);
    }
}
