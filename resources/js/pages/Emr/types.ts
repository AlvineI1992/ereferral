export type EmrRecord = {
    emr_id: number;
    emr_name: string;
    status: number | string | boolean;
    remarks: string | null;
    assigned_facilities_count?: number;
    active_facilities_count?: number;
    inactive_facilities_count?: number;
    coverage_regions_count?: number;
    coverage_types_count?: number;
    created_at?: string | null;
    updated_at?: string | null;
};

export type EmrPermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
    canAssign?: boolean;
};

export type EmrFacilityRow = {
    hfhudcode: string;
    facility_name: string;
    status: string;
    regname: string | null;
    description: string | null;
    fhudaddress: string | null;
};
