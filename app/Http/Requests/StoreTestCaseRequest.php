<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestCaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'string', 'max:50000', 'required_without:file'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,txt', 'max:5120', 'required_without:text'],
            'output_language' => ['required', Rule::in(['en', 'vi'])],
        ];
    }

    public function messages(): array
    {
        return [
            'file.mimes' => 'File phải có định dạng: pdf, doc, docx hoặc txt.',
            'file.max' => 'File không được vượt quá 5MB.',
            'text.max' => 'Nội dung yêu cầu không được vượt quá 50.000 ký tự.',
        ];
    }
}
