import axios from 'axios';

export type EncryptionState = {
    enabled: boolean;
    status: string;
    processedRows: number;
    totalRows: number;
    lastError: string | null;
    backupVerified: boolean;
    preflight: { keyConfigured: boolean; blindIndexesReady: boolean; settingsReady: boolean; converterReady: boolean };
};

export async function getEncryptionStatus(endpoint: string, signal: AbortSignal): Promise<EncryptionState> {
    return (await axios.get<EncryptionState>(`${endpoint}/status`, { signal })).data;
}

export async function activateEncryption(endpoint: string, confirmation: string) {
    return (await axios.put<{ message: string; encryption: EncryptionState }>(endpoint, { enabled: true, confirmation })).data;
}
