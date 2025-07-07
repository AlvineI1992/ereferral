<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatientReferralRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    
    public function rules(): array
    {
       
        return [
            // Referral rules
            'referral.facility_from' => 'required|string|max:50',
            'referral.facility_to' => 'required|string|max:50',
            'referral.phic_pan' => 'nullable|string|max:20',
            //'referral.contact_no' => 'required|string|max:11',
            'referral.type_referral' => 'required|string|max:10',
            'referral.category' => 'required|string|max:2',
            'referral.reason' => 'required|string|max:50',
            'referral.other_reason' => 'nullable|string|max:255',
            'referral.remarks' => 'nullable|string|max:255',
            'referral.contact_person' => 'required|string|max:100',
            'referral.designation' => 'nullable|string|max:100',
            'referral.refer_date' => 'required|date',
            'referral.refer_time' => 'required|string|max:12',

            // Patient rules
            'patient.family_number' => 'nullable|string',
            'patient.phic_number' => 'nullable|string',
            'patient.case_no' => 'nullable|string|max:50',
            'patient.last_name' => 'required|string|max:100',
            'patient.first_name' => 'required|string|max:100',
            'patient.middle_name' => 'nullable|string',
            'patient.suffix' => 'nullable|string',
            'patient.birthdate' => 'required|date',
            'patient.sex' => 'required|in:M,F',
            'patient.civil_status' => 'nullable|string',
            'patient.religion' => 'required|string|max:50',
            'patient.contact_no' => 'nullable|string|max:15',
            'patient.blood_type' => 'nullable|string|max:1',
            'patient.blood_rh' => 'nullable|string|max:1',

            // Demographics rules
            'demographics.street' => 'required|string|max:255',
            'demographics.brgy_code' => 'required|string|max:50',
            'demographics.city_code' => 'required|string|max:50',
            'demographics.prov_code' => 'required|string|max:50',
            'demographics.reg_code' => 'required|string|max:50',
            'demographics.zipcode' => 'required|numeric|digits:4',

            // Clinical rules
            'clinical.diagnosis' => 'required|array|min:1',
            'clinical.diagnosis.*' => 'required|string|max:255',

            'clinical.chief_complaint' => 'required|string|max:255',
            'clinical.history' => 'nullable|string|max:255',
            'clinical.findings' => 'nullable|string|max:255',
            //'clinical.physical_examination' => 'nullable|string|max:255',
           // 'vital_signs'=>'nullable|string|max:255',
            'vital_signs.BP' => 'nullable|string|max:20',
            'vital_signs.temp' => 'nullable|string|max:5',
            'vital_signs.HR' => 'nullable|string|max:5',
            'vital_signs.RR' => 'nullable|string|max:5',
            'vital_signs.O2_sats' => 'nullable|string|max:5',
            'vital_signs.weight' => 'nullable|numeric',
            'vital_signs.height' => 'nullable|numeric',

            // ICD codes
           // Validate ICD is an array with at least one item
         'ICD' => 'required|array|min:1',
         'ICD.*' => ['required', 'string', 'regex:/^[A-Z][0-9]{2}(\.[0-9A-Z]{1,4})?$/'],

            // Vital signs rules
          

            // Patient providers rules
        'patient_providers' => 'required|array|min:1',
        'patient_providers.*.provider_last' => 'required|string|max:255',
        'patient_providers.*.provider_first' => 'required|string|max:255',
        'patient_providers.*.provider_middle' => 'nullable|string|max:255',
        'patient_providers.*.provider_suffix' => 'nullable|string|max:255',
        'patient_providers.*.ProviderContactNo' => 'nullable|string|max:50',
        'patient_providers.*.provider_type' => 'required|string|in:REFER,CONSU',
        ];

        
    }

    public function messages(): array
    {
        return [
            // Referral rules
            'referral.facility_from' => 'Referring facility is required!',
            'referral.facility_to' => 'Referral facility is required!',
            'referral.phic_pan' => 'Phic pan is required!',
           // 'referral.contact_no' => 'Contact number is required',
            'referral.type_referral' => 'Type of referral is required',
            'referral.category' => 'Referral Category is required',
            'referral.reason' => 'Referral reason is required',
           /*  'referral.other_reason' => 'nullable|string|max:255', */
          /*   'referral.remarks' => 'nullable|string|max:255', */
           'referral.contact_person' => 'Referral contact person is required!',
         /*    'referral.designation' => 'nullable|string|max:100', */
            'referral.refer_date' => 'Referral date is required',
            'referral.refer_time' => 'Referral time is required',

            // Patient rules
           
            'patient.case_no' => 'Case no is required',
            'patient.last_name' => 'Patient last name is required!',
            'patient.first_name' => 'Patient first name is required!',
           /*  'patient.middle_name' => 'Patient middle name is re', */
            'patient.birthdate' => 'Patient birthdate is required!',
            'patient.sex' =>'Patient sex is required!',
           /*  'patient.religion' => 'Patient religion is required', */
           /*  'patient.contact_no' => 'nullable|string|max:15', */

            // Demographics rules
            'demographics.street' => 'Street address is required',
            'demographics.brgy_code' => 'Barangay code is required | Please refer to the references',
            'demographics.city_code' => 'City code is required | Please refer to the references',
            'demographics.prov_code' => 'Province code is required | Please refer to the references',
            'demographics.reg_code' => 'Region code is required | Please refer to the references',
           /*  'demographics.zipcode' => 'required|numeric|digits:4', */

            // Clinical rules
            'clinical.diagnosis' => 'Diagnosis is required!',
            'clinical.chief_complaint' => 'Chief complaint is required!',

            // ICD codes
            'ICD' => 'ICD 10 code is required!',
            'ICD.*' => 'string|max:10',

            // Patient providers rules
            'patient_providers' => 'required|array',
            'patient_providers.*.provider_last' => 'Provider last name is required!',
            'patient_providers.*.provider_first' => 'Provider last name is required!',
            'patient_providers.*.provider_middle' => 'Provider last name is required!',
           // 'patient_providers.*.provider_contact_no' => 'Provider last name is required!',
            'patient_providers.*.provider_type' =>'Provider type is required!',
        ];

        
    }

   
} 
