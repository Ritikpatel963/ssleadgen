<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
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
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
             'status' => 'nullable|string|max:80',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_source' => 'nullable|string|max:150',
            'estimated_cover' => 'nullable|numeric|min:0',
            'policy_no' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:100',
            'login_date' => 'nullable|date',
            'anp' => 'nullable|numeric',
            'dob' => 'nullable|date',
            'address' => 'nullable|string|max:200',
            'suburb' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'post_code' => 'nullable|string|max:20',
            'adviser' => 'nullable|string|max:100',
            'not_counting' => 'nullable|string|max:10',
            'compliance_by' => 'nullable|string|max:100',
            'roa_due_date' => 'nullable|date',
            'status_compliance' => 'nullable|string|max:50',
            'sent_to_client' => 'nullable|string|max:50',
            'outcome' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:5000',
        ];
    }
}
