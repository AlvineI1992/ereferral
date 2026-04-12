export type BreadcrumbItem = {
    title: string;
    href: string;
};

export type PermissionProps = {
    canCreate: boolean;
    canEdit: boolean;
    canDelete: boolean;
    canView: boolean;
    refreshKey?: number;
    onEdit?: (value: IncomingReferralRow) => void;
    onCreated?: () => void;
};

export type IncomingReason = {
    code: string;
    label: string;
    count: number;
};

export type IncomingGeoItem = {
    code: string;
    label: string;
    count: number;
};

export type IncomingSummary = {
    totalIncoming: number;
    todayIncoming: number;
    emergencyCount: number;
    outpatientCount: number;
    receivingFacilities: number;
    topReasons: IncomingReason[];
    topProvinces: IncomingGeoItem[];
    topCities: IncomingGeoItem[];
    topBarangays: IncomingGeoItem[];
    generatedAt: string;
};

export type IncomingReferralRow = {
    LogID: string;
    avatar?: string;
    patient_name: string;
    patient_sex: string;
    patient_birthdate: string;
    patient_civilstatus: string;
    referral_origin_name: string;
    referral_destination_name: string;
    referral_type_description: string;
    referral_category: string;
    referral_reason_description: string;
    referral_date: string;
    referral_time: string;
};
