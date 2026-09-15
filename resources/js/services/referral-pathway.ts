import axios from 'axios';

export type JourneyStep = {
    LogID: string;
    parent_LogID: string | null;
    sequence: number;
    status: string;
    referred_at: string;
    source: { code: string; name: string };
    destination: { code: string; name: string };
    received_at: string | null;
    admitted_at: string | null;
    discharged_at: string | null;
    forwarded_at: string | null;
    details: {
        referralReason?: string;
        remarks?: string;
        referringProvider?: string;
        referringProviderContactNumber?: string;
        referralCategory?: string;
    };
    clinical: { clinicalDiagnosis?: string; clinicalHistory?: string; chiefComplaint?: string; findings?: string } | null;
    cancellation: { reason: string; cancelled_at: string } | null;
    status_history: { referral_status: string; remarks: string; created_at: string }[];
};
export type Journey = { root_LogID: string; current_LogID: string; can_forward: boolean; transactions: JourneyStep[] };
export type ForwardPayload = {
    LogID: string;
    request_id: string;
    facility_to: string;
    reason: string;
    other_reason: string;
    remarks: string;
    referring_provider: string;
    contact_number: string;
    clinical_update: string;
};
export type JourneyOptions = { facilities: { hfhudcode: string; facility_name: string }[]; reasons: { code: string; description: string }[] };

export async function loadJourney(LogID: string) {
    return (await axios.get<Journey>('/referrals/pathway', { params: { LogID } })).data;
}
export async function pathwayOptions(search: string, signal: AbortSignal) {
    return (await axios.get<JourneyOptions>('/referrals/pathway/options', { params: { search }, signal })).data;
}
export async function forwardReferral(payload: ForwardPayload) {
    return (await axios.post<{ data: { LogID: string } }>('/referrals/pathway', payload)).data;
}
