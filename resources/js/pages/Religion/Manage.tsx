import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { PanelLeftClose, PanelLeftOpen, Plus, ScrollText } from 'lucide-react';
import { useState } from 'react';
import Form from './Form';
import List from './List';
import { type ReligionRecord } from './types';

export default function Manage() {
    const [selectedReligion, setSelectedReligion] = useState<ReligionRecord | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [isFormOpen, setIsFormOpen] = useState(true);

    const handleEdit = (record: ReligionRecord) => {
        setSelectedReligion(record);
        setIsFormOpen(true);
    };

    const handleCreatedOrUpdated = () => {
        setSelectedReligion(null);
        setRefreshKey((prev) => prev + 1);
    };

    const handleCancelEdit = () => {
        setSelectedReligion(null);
    };

    return (
        <div className="space-y-4 p-4">
            <section className="border-primary/15 from-primary/10 via-background to-background rounded-2xl border bg-gradient-to-r p-5 shadow-sm">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div className="space-y-2">
                        <p className="text-primary text-sm font-medium">Reference Master</p>
                        <div>
                            <h1 className="text-2xl font-semibold tracking-tight">Manage religions</h1>
                            <p className="text-muted-foreground text-sm">
                                Maintain religion values used by patient intake and make `/api/religions` load from a real master table.
                            </p>
                        </div>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Badge>Create</Badge>
                        <Badge>Edit</Badge>
                        <Badge>Delete</Badge>
                        <Badge variant="outline">API Ready</Badge>
                    </div>
                </div>
            </section>

            <div className={cn('grid gap-4', isFormOpen ? 'xl:grid-cols-[360px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {isFormOpen && <Form formval={selectedReligion} onCreated={handleCreatedOrUpdated} onCancel={handleCancelEdit} />}

                <div className="space-y-4">
                    <div className="bg-background flex flex-col gap-3 rounded-xl border p-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={() => setIsFormOpen((prev) => !prev)}
                                title={isFormOpen ? 'Hide religion form' : 'Show religion form'}
                            >
                                {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                            </Button>

                            <div>
                                <p className="text-sm font-medium">
                                    {selectedReligion ? `Editing ${selectedReligion.reldesc}` : 'Religion form'}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {selectedReligion ? 'Save updates for the selected religion.' : 'Open the form to register a new religion.'}
                                </p>
                            </div>
                        </div>

                        <Button
                            type="button"
                            onClick={() => {
                                setSelectedReligion(null);
                                setIsFormOpen(true);
                            }}
                        >
                            <Plus className="size-4" />
                            New Religion
                        </Button>
                    </div>

                    <div className="rounded-xl border border-dashed bg-slate-50/60 p-4 text-sm text-slate-600">
                        <div className="flex items-start gap-3">
                            <ScrollText className="mt-0.5 size-4 text-teal-700" />
                            <p>
                                Active records from this module are what `/api/religions` now returns by default. Keep codes stable once they are in use.
                            </p>
                        </div>
                    </div>

                    <List refreshKey={refreshKey} onEdit={handleEdit} />
                </div>
            </div>
        </div>
    );
}
