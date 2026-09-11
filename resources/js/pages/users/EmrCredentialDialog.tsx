import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import axios from 'axios';
import { useState } from 'react';
import { toast } from 'sonner';
import { type UserRecord } from './types';

export default function EmrCredentialDialog({ user }: { user: UserRecord }) {
    const [open, setOpen] = useState(false);
    const [token, setToken] = useState('');
    const [busy, setBusy] = useState(false);
    const [savedStatus, setSavedStatus] = useState('');

    const openDialog = async () => {
        setOpen(true);
        setBusy(true);
        setSavedStatus('Checking saved token...');
        try {
            const { data } = await axios.get<{ saved: boolean; active: boolean }>(`/users/${user.id}/emr-credential`);
            setSavedStatus(data.active ? 'A token is saved and active for this account and provider.' : data.saved ? 'A token is saved but inactive. Check account status and provider assignment.' : 'No token is saved. Generate one to enable EMR API access.');
        } catch {
            setSavedStatus('Unable to check saved token status. Close and retry.');
        } finally { setBusy(false); }
    };

    const updateCredential = async (revoke: boolean) => {
        setBusy(true);
        setToken('');
        try {
            const url = `/users/${user.id}/emr-credential`;
            if (revoke) {
                await axios.delete(url);
                toast.success('EMR credential revoked.');
                setSavedStatus('No token is saved. The previous token has been revoked.');
            } else {
                const response = await axios.post<{ emr_id_token: string }>(url);
                setToken(response.data.emr_id_token);
                setSavedStatus('Token saved successfully for this account and provider.');
                toast.success('EMR token saved.');
            }
        } catch (error) {
            toast.error(axios.isAxiosError(error) ? error.response?.data?.message ?? 'Unable to update EMR credential.' : 'Unable to update EMR credential.');
        } finally {
            setBusy(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(value) => { if (!busy) { setOpen(value); setToken(''); } }}>
            <Button type="button" variant="outline" size="sm" onClick={() => void openDialog()}>Manage EMR token</Button>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>EMR credential — {user.name}</DialogTitle>
                    <DialogDescription>Generate a credential for this account. Generating again immediately replaces its previous credential. Revoking stops its use.</DialogDescription>
                </DialogHeader>
                <p role="status" className="text-sm font-medium">{savedStatus}</p>
                <p className="text-sm">Use it in the X-EMR-Token header with this account’s bearer token for GET /api/get-referral-list/&#123;fhudcode&#125;.</p>
                {token && (
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Copy now. This credential is only shown once.</p>
                        <Input aria-label="Generated EMR credential" readOnly value={token} className="font-mono text-xs" onFocus={(event) => event.target.select()} />
                        <Button type="button" variant="outline" onClick={async () => {
                            try { await navigator.clipboard.writeText(token); toast.success('Credential copied.'); }
                            catch { toast.error('Select and copy the credential manually.'); }
                        }}>Copy credential</Button>
                    </div>
                )}
                <div className="flex flex-wrap gap-2">
                    <Button type="button" disabled={busy || user.status !== 'A' || !user.access_id} onClick={() => void updateCredential(false)}>Generate EMR token</Button>
                    <Button type="button" variant="destructive" disabled={busy} onClick={() => void updateCredential(true)}>Revoke credential</Button>
                </div>
                {(user.status !== 'A' || !user.access_id) && <p className="text-muted-foreground text-sm">Activate this account and assign an EMR provider before generating a token.</p>}
            </DialogContent>
        </Dialog>
    );
}
