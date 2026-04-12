export type FacilityPermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
};

export type FacilityRecord = {
    hfhudcode: string;
    facility_name: string;
    status: string;
    regname: string | null;
    description: string | null;
    fhudaddress: string | null;
};
