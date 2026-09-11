<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\DataEncryptionManager;
use App\Rules\UniqueEncryptedEmail;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                app(DataEncryptionManager::class)->isEnabled()
                    ? new UniqueEncryptedEmail($this->user()->id)
                    : Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];
    }
}
