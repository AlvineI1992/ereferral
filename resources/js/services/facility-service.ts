import axios from 'axios';

export interface FacilityListItem {
    hfhudcode: string;
    facility_name: string | null;
    status: string | null;
    regname: string | null;
    description: string | null;
    fhudaddress: string | null;
}

interface FacilityListParams {
    page: number;
    search: string;
    perPage: number;
    emr_id?: string;
}

interface FacilityListResponse {
    data: FacilityListItem[];
    total: number;
    current_page: number;
    last_page: number;
}

export async function getFacilities(params: FacilityListParams, signal?: AbortSignal): Promise<FacilityListResponse> {
    const response = await axios.get<FacilityListResponse>('/facility/list', { params, signal });
    return response.data;
}

export async function revokeProviderFacilities(providerId: string, facilities: string[]): Promise<void> {
    await axios.post('/emr/revoke', { emr_id: providerId, facilities });
}
