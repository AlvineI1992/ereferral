export type PermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
    canAssign: boolean;
};

export type AccessType = 'EMR' | 'CHD' | 'HOSP' | '';

export type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    access_id: string;
    access_type: AccessType;
    status: boolean;
};

export type UserRecord = {
    id: number;
    name: string;
    email: string;
    status: string;
    status_label?: string;
    access_id: string | null;
    access_type: AccessType | null;
    access_label?: string | null;
    roles?: string[];
    roles_count?: number;
    primary_role?: string | null;
};

export type ProviderOption = {
    emr_id: string;
    emr_name: string;
};

export type RegionOption = {
    regcode: string;
    regname: string;
};

export type HospitalOption = {
    hfhudcode: string;
    facility_name: string;
};
