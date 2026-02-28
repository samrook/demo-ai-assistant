<?php

namespace App\Http\Requests;

use App\Models\AiConversation;
use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /**
         * @var AiConversation|null $conversation
         */
        $conversation = $this->route('conversation');
        
        return $conversation
            ? $this->user()->can('update', $conversation)
            : true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string', 'max:5000'],
            'use_rag' => ['boolean'],
        ];
    }
}
