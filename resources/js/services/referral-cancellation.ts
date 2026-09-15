import axios from 'axios';

export async function cancelWebReferral(LogID: string, reason: string): Promise<void> {
    await axios.post('/referrals/cancel', { LogID, reason });
}
