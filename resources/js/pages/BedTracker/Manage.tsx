import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { BedDouble, PanelLeftClose, PanelLeftOpen, Plus, ScrollText } from 'lucide-react';
import { useMemo, useState } from 'react';
import Form from './Form';
import List from './List';
import { type BedTrackerPermissionProps } from './types';

export default function Manage({ canCreate, canEdit, canDelete, canView }: BedTrackerPermissionProps) {
    const [selectedId, setSelectedId] = useState<number | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [isFormOpen, setIsFormOpen] = useState(canCreate || canEdit);

    const badges = useMemo(
        () => [canCreate ? 'Create' : null, canEdit ? 'Edit' : null, canDelete ? 'Delete' : null, canView ? 'View' : null].filter(Boolean) as string[],
        [canCreate, canDelete, canEdit, canView],
    );

    const handleSaved = () => {
        setSelectedId(null);
        setRefreshKey((current) => current + 1);
    };

    return (
        <div className="space-y-4 p-4">
            <section className="rounded-3xl border border-sky-900/10 bg-[radial-gradient(circle_at_top_right,_rgba(125,211,252,0.24),_transparent_30%),linear-gradient(135deg,_#0a2a43_0%,_#0f4d6d_55%,_#0c7a84_100%)] p-6 text-white shadow-sm">
                <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div className="max-w-3xl space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge className="bg-white/12 text-white hover:bg-white/12">Operations</Badge>
                            <Badge className="border-white/20 bg-sky-300/15 text-sky-50 hover:bg-sky-300/15">Facility Scoped</Badge>
                            {badges.map((badge) => (
                                <Badge key={badge} className="border-white/15 bg-white/8 text-sky-50 hover:bg-white/8">
                                    {badge}
                                </Badge>
                            ))}
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight">Bed tracker workspace</h1>
                            <p className="max-w-2xl text-sm text-sky-50/85">
                                Track facility bed capacity, occupancy, and reserved slots from one workspace. Access is scoped automatically by hospital, provider coverage, or regional coverage.
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm xl:min-w-[280px]">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-white/12 p-3">
                                <BedDouble className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium tracking-[0.24em] text-sky-100/70 uppercase">Tracker</p>
                                <p className="text-lg font-semibold">Live bed capacity</p>
                                <p className="text-sm text-sky-50/80">Total, occupied, reserved, available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div className={cn('grid gap-4', isFormOpen ? 'xl:grid-cols-[400px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {isFormOpen && (canCreate || (canEdit && selectedId !== null)) && (
                    <Form canCreate={canCreate} canEdit={canEdit} recordId={selectedId} onSaved={handleSaved} onCancel={() => setSelectedId(null)} />
                )}

                <div className="space-y-4">
                    <div className="flex flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={() => setIsFormOpen((prev) => !prev)}
                                title={isFormOpen ? 'Hide bed tracker form' : 'Show bed tracker form'}
                            >
                                {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                            </Button>

                            <div>
                                <p className="text-sm font-medium">{selectedId !== null ? 'Editing selected tracker record' : 'Bed tracker records'}</p>
                                <p className="text-muted-foreground text-xs">Manage bed categories per facility and keep occupancy values current.</p>
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
                                New Record
                            </Button>
                        )}
                    </div>

                    <div className="rounded-2xl border border-dashed bg-sky-50/60 p-4 text-sm text-slate-700">
                        <div className="flex items-start gap-3">
                            <ScrollText className="mt-0.5 size-4 text-sky-700" />
                            <p>
                                Availability is calculated as `total - occupied - reserved`. Records are limited to facilities allowed by the current account access scope.
                            </p>
                        </div>
                    </div>

                    <List canEdit={canEdit} canDelete={canDelete} refreshKey={refreshKey} onEdit={setSelectedId} />
                </div>
            </div>
        </div>
    );
}
