import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { PanelLeftClose, PanelLeftOpen, Plus, ScrollText, Users } from 'lucide-react';
import { useMemo, useState } from 'react';
import Form from './Form';
import List from './List';
import { type PatientPermissionProps } from './types';

export default function Manage({ canCreate, canEdit, canDelete, canView }: PatientPermissionProps) {
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [isFormOpen, setIsFormOpen] = useState(canCreate || canEdit);

    const permissionBadges = useMemo(
        () => [
            canCreate ? 'Create' : null,
            canEdit ? 'Edit' : null,
            canDelete ? 'Delete' : null,
            canView ? 'View' : null,
        ].filter(Boolean) as string[],
        [canCreate, canDelete, canEdit, canView],
    );

    const handleCreatedOrUpdated = () => {
        setSelectedId(null);
        setRefreshKey((prev) => prev + 1);
    };

    return (
        <div className="space-y-4 p-4">
            <section className="rounded-3xl border border-emerald-900/10 bg-[radial-gradient(circle_at_top_right,_rgba(110,231,183,0.28),_transparent_32%),linear-gradient(135deg,_#073b3a_0%,_#0f5f53_52%,_#18826b_100%)] p-6 text-white shadow-sm">
                <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div className="max-w-3xl space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge className="bg-white/12 text-white hover:bg-white/12">Master List</Badge>
                            <Badge className="border-white/20 bg-emerald-300/15 text-emerald-50 hover:bg-emerald-300/15">Add / Edit / Delete</Badge>
                            {permissionBadges.map((badge) => (
                                <Badge key={badge} className="border-white/15 bg-white/8 text-emerald-50 hover:bg-white/8">
                                    {badge}
                                </Badge>
                            ))}
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight">Patient registry workspace</h1>
                            <p className="max-w-2xl text-sm text-emerald-50/85">
                                Maintain the patient master list used for intake. Records are checked against the same identity markers used during referral save to avoid duplicate patient entries.
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm xl:min-w-[280px]">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-white/12 p-3">
                                <Users className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium tracking-[0.24em] text-emerald-100/70 uppercase">Registry</p>
                                <p className="text-lg font-semibold">Patient master list</p>
                                <p className="text-sm text-emerald-50/80">Deduplicated by identity</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div className={cn('grid gap-4', isFormOpen ? 'xl:grid-cols-[420px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {isFormOpen && (canCreate || (canEdit && selectedId !== null)) && (
                    <Form
                        canCreate={canCreate}
                        canEdit={canEdit}
                        patientId={selectedId}
                        onCreated={handleCreatedOrUpdated}
                        onCancel={() => setSelectedId(null)}
                    />
                )}

                <div className="space-y-4">
                    <div className="flex flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={() => setIsFormOpen((prev) => !prev)}
                                title={isFormOpen ? 'Hide patient form' : 'Show patient form'}
                            >
                                {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                            </Button>

                            <div>
                                <p className="text-sm font-medium">
                                    {selectedId !== null ? 'Editing selected patient' : canCreate ? 'Register a patient' : 'Patient registry list'}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {selectedId !== null
                                        ? 'Update the patient master record and demographic details.'
                                        : 'Use the form to maintain patient records and keep demographic codes aligned.'}
                                </p>
                            </div>
                        </div>

                        {canCreate && (
                            <Button
                                type="button"
                                onClick={() => {
                                    setSelectedId(null);
                                    setIsFormOpen(true);
                                }}
                            >
                                <Plus className="size-4" />
                                New Patient
                            </Button>
                        )}
                    </div>

                    <div className="rounded-2xl border border-dashed bg-emerald-50/60 p-4 text-sm text-slate-700">
                        <div className="flex items-start gap-3">
                            <ScrollText className="mt-0.5 size-4 text-emerald-700" />
                            <p>
                                Duplicate blocking uses first name, middle name, last name, suffix, birth date, and civil status. Edit keeps the current record excluded from that duplicate check.
                            </p>
                        </div>
                    </div>

                    <List canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={setSelectedId} />
                </div>
            </div>

            {!canCreate && !canEdit && !canView && (
                <div className="rounded-2xl border bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                    No patient registry permissions are assigned to this account.
                </div>
            )}
        </div>
    );
}
