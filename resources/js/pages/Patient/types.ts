export type PatientPermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
};

export type PatientRecord = {
    id: number;
    legacy_log_id?: string | null;
    full_name: string;
    first_name?: string | null;
    middle_name?: string | null;
    last_name?: string | null;
    suffix?: string | null;
    birth_date?: string | null;
    birth_date_label?: string | null;
    sex?: string | null;
    sex_label?: string | null;
    civil_status?: string | null;
    civil_status_label?: string | null;
    religion?: string | null;
    contact_number?: string | null;
    address?: string | null;
    region_name?: string | null;
    province_name?: string | null;
    city_name?: string | null;
    barangay_name?: string | null;
    zip_code?: string | null;
};

export type PatientDetail = {
    id: number;
    legacy_log_id?: string | null;
    family_id?: string | null;
    phic_number?: string | null;
    case_number?: string | null;
    last_name?: string | null;
    first_name?: string | null;
    middle_name?: string | null;
    suffix?: string | null;
    birth_date?: string | null;
    sex?: string | null;
    contact_number?: string | null;
    religion?: string | null;
    blood_type?: string | null;
    blood_type_rh?: string | null;
    civil_status?: string | null;
    street_address?: string | null;
    barangay_code?: string | null;
    city_code?: string | null;
    province_code?: string | null;
    region_code?: string | null;
    zip_code?: string | null;
};

export type ReligionOption = {
    relcode: string;
    reldesc: string;
};
