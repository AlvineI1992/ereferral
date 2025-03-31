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

    public function rules()
    {
        return [
            'fhudFrom' => 'required|string',
            'fhudTo' => 'required|string',
            'patientLastName' => 'required|string',
            'patientFirstName' => 'required|string',
            'patientBirthDate' => 'required|date',
            'patientSex' => 'required|string|in:M,F,other',
            'referralDate' => 'required|date',
            'referralTime' => 'required|date_format:h:iA',
            'typeOfReferral' => 'required|string',
            'patientStreetAddress' => 'required|string|max:255',
            'patientBrgyAddress' => 'required|string|max:255',
            'patientMunAddress' => 'required|string|max:255',
            'patientProvAddress' => 'required|string|max:255',
            'patientRegAddress' => 'required|string|max:255',
            'patientZipAddress' => 'required|string|max:255',
            'clinicalDiagnosis' => 'required|string|max:255',
            'clinicalHistory' => 'required|string|max:255',
            'vitalSign.BP' => 'required|string|max:50',
            'vitalSign.Temp' => 'required|string|max:50',
            'vitalSign.HR' => 'required|string|max:50',
            'vitalSign.RR' => 'required|string|max:50',
            'vitalSign.O2Sats' => 'required|string|max:50',
            'vitalSign.Weight' => 'required|string|max:50',
            'vitalSign.Height' => 'required|string|max:50',
            'physicalExamination' => 'required|string|max:255',
            'chiefComplaint' => 'required|string|max:255',
            'findings' => 'required|string|max:255',
        ];
    }

    public function messages()
    {
        return [
            'fhudFrom.required' => 'Referring facility code is required!',
            'fhudTo.required' => 'Referral facility code is required!',
            'patientLastName.required' => 'Patient last name is required.',
            'patientFirstName.required' => 'Patient first name is required!',
            'patientSex.required' => 'Patient sex is required!',
            'patientBirthDate.required' => 'Patient birthdate is required!',
             'patientStreetAddress.required' => 'Street address is required.',
             'patientBrgyAddress.required' => 'Barangay address is required.',
             'patientMunAddress.required' => 'Municipality address is required.',
             'patientProvAddress.required' => 'Province address is required.',
             'patientRegAddress.required' => 'Region address is required.',
             'patientZipAddress.required' => 'Zip address is required.',
             'clinicalDiagnosis.required' => 'Clinical diagnosis is required.',
             'clinicalHistory.required' => 'Clinical history is required.',
             'vitalSign.BP.required' => 'Blood pressure is required.',
             'vitalSign.Temp.required' => 'Temperature is required.',
             'vitalSign.HR.required' => 'Heart rate is required.',
             'vitalSign.RR.required' => 'Respiratory rate is required.',
             'vitalSign.O2Sats.required' => 'Oxygen saturation is required.',
             'vitalSign.Weight.required' => 'Weight is required.',
             'vitalSign.Height.required' => 'Height is required.',
             'physicalExamination.required' => 'Physical examination details are required.',
             'chiefComplaint.required' => 'Chief complaint is required.',
             'findings.required' => 'Findings are required.',
        ];
    }
}


