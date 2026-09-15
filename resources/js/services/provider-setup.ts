import type { EmrRecord } from '@/pages/Emr/types';
import axios from 'axios';

export async function createSetupProvider(values: { emr_name: string; status: boolean; remarks: string }): Promise<EmrRecord> {
    const response = await axios.post<{ data: EmrRecord }>(route('emr.store'), values);
    return response.data.data;
}

export async function generateSetupToken(userId: number): Promise<string> {
    const response = await axios.post<{ emr_id_token: string }>(route('users.emr-credential.store', userId));
    return response.data.emr_id_token;
}
