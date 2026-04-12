export type BedTrackerPermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
};

export type BedTrackerRecord = {
    id: number;
    facility_hfhudcode: string;
    facility_name: string | null;
    region_name: string | null;
    bed_type: string;
    total_beds: number;
    occupied_beds: number;
    reserved_beds: number;
    available_beds: number;
    occupancy_rate: number;
    status: string;
    status_label?: string;
    remarks?: string | null;
    updated_by?: string | null;
    updated_at?: string | null;
};

export type BedTrackerDetail = {
    id: number;
    facility_hfhudcode: string;
    facility_name?: string | null;
    bed_type: string;
    total_beds: number;
    occupied_beds: number;
    reserved_beds: number;
    available_beds: number;
    status: string;
    remarks?: string | null;
    updated_by?: string | null;
    updated_at?: string | null;
};

export type FacilityOption = {
    hfhudcode: string;
    facility_name: string;
    region_code?: string | null;
    emr_id?: string | null;
};
