<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\ContactList;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContactImportService
{
    /**
     * Parse and import contacts from an uploaded CSV file.
     *
     * @param UploadedFile $file
     * @param Organization $organization
     * @param int|null $contactListId
     * @return array
     */
    public function import(UploadedFile $file, Organization $organization, ?int $contactListId = null): array
    {
        $stats = [
            'total_rows' => 0,
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $targetList = null;
        if ($contactListId) {
            $targetList = $organization->contactLists()->find($contactListId);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            $stats['errors'][] = 'Unable to read the CSV file.';
            return $stats;
        }

        // Read header row
        $headers = fgetcsv($handle, 1000, ',');
        if (!$headers) {
            fclose($handle);
            $stats['errors'][] = 'The uploaded CSV file is empty.';
            return $stats;
        }

        // Normalize header names (lowercase and trimmed)
        $headerMap = [];
        foreach ($headers as $index => $header) {
            $cleaned = strtolower(trim($header));
            if (in_array($cleaned, ['email', 'e-mail', 'email_address'])) {
                $headerMap['email'] = $index;
            } elseif (in_array($cleaned, ['first_name', 'firstname', 'first name', 'name'])) {
                $headerMap['first_name'] = $index;
            } elseif (in_array($cleaned, ['last_name', 'lastname', 'last name', 'surname'])) {
                $headerMap['last_name'] = $index;
            }
        }

        if (!isset($headerMap['email'])) {
            fclose($handle);
            $stats['errors'][] = 'CSV file must include an "email" header column.';
            return $stats;
        }

        $rowNumber = 1;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            $rowNumber++;
            $stats['total_rows']++;

            // Extract email
            $rawEmail = isset($headerMap['email']) && isset($row[$headerMap['email']])
                ? trim($row[$headerMap['email']])
                : null;

            $firstName = isset($headerMap['first_name']) && isset($row[$headerMap['first_name']])
                ? trim($row[$headerMap['first_name']])
                : null;

            $lastName = isset($headerMap['last_name']) && isset($row[$headerMap['last_name']])
                ? trim($row[$headerMap['last_name']])
                : null;

            // Validate email address
            $validator = Validator::make(
                ['email' => $rawEmail],
                ['email' => 'required|email|max:255']
            );

            if ($validator->fails()) {
                $stats['skipped']++;
                $stats['errors'][] = "Row {$rowNumber}: Invalid email address '{$rawEmail}'.";
                continue;
            }

            DB::transaction(function () use (
                $organization,
                $rawEmail,
                $firstName,
                $lastName,
                $targetList,
                &$stats
            ) {
                // Find existing contact within this organization or create a new one
                $contact = Contact::where('organization_id', $organization->id)
                    ->where('email', strtolower($rawEmail))
                    ->first();

                if ($contact) {
                    // Update names if provided
                    $updated = false;
                    if ($firstName && $contact->first_name !== $firstName) {
                        $contact->first_name = $firstName;
                        $updated = true;
                    }
                    if ($lastName && $contact->last_name !== $lastName) {
                        $contact->last_name = $lastName;
                        $updated = true;
                    }
                    if ($updated) {
                        $contact->save();
                    }
                    $stats['updated']++;
                } else {
                    $contact = Contact::create([
                        'organization_id' => $organization->id,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => strtolower($rawEmail),
                        'status' => 'subscribed',
                    ]);
                    $stats['imported']++;
                }

                // Attach to list if requested
                if ($targetList) {
                    $targetList->contacts()->syncWithoutDetaching([$contact->id]);
                }
            });
        }

        fclose($handle);

        return $stats;
    }
}