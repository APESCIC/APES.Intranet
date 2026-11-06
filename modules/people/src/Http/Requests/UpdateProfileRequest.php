<?php

namespace Modules\People\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'job_title' => ['nullable', 'string', 'max:120'],
            'team' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'location' => ['nullable', 'string', 'max:120'],
            'skills' => ['nullable', 'string', 'max:1000'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'emergency_contact_name' => ['nullable', 'string', 'max:120'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }

    /**
     * @return list<string>
     */
    public function skillsList(): array
    {
        $raw = (string) $this->input('skills', '');

        return array_values(array_filter(array_map(
            static fn (string $part) => trim($part),
            preg_split('/[,\\n]+/', $raw) ?: [],
        )));
    }
}
