import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { Building2, Landmark, MapPinned, PanelLeftClose, PanelLeftOpen, Plus, Route, ScrollText } from 'lucide-react';
import { useMemo, useState } from 'react';
import Form from './Form';
import List from './List';
import { DEMOGRAPHIC_LEVELS, type DemographicLevel, type DemographicRecord, type DemographicScope } from './types';

const levelIcons = {
    region: Landmark,
    province: Building2,
    city: Route,
    barangay: MapPinned,
};

export default function Manage() {
    const [level, setLevel] = useState<DemographicLevel>('region');
    const [selectedRecord, setSelectedRecord] = useState<DemographicRecord | null>(null);
    const [refreshKey, setRefreshKey] = useState(0);
    const [isFormOpen, setIsFormOpen] = useState(true);
    const [scope, setScope] = useState<DemographicScope>({
        regionCode: '',
        provinceCode: '',
        cityCode: '',
    });

    const activeLevel = useMemo(() => DEMOGRAPHIC_LEVELS.find((item) => item.value === level) ?? DEMOGRAPHIC_LEVELS[0], [level]);

    const handleLevelChange = (nextLevel: DemographicLevel) => {
        setLevel(nextLevel);
        setSelectedRecord(null);

        setScope((current) => {
            if (nextLevel === 'region') {
                return { regionCode: '', provinceCode: '', cityCode: '' };
            }

            if (nextLevel === 'province') {
                return { regionCode: current.regionCode, provinceCode: '', cityCode: '' };
            }

            if (nextLevel === 'city') {
                return { regionCode: current.regionCode, provinceCode: current.provinceCode, cityCode: '' };
            }

            return current;
        });
    };

    const handleCreatedOrUpdated = () => {
        setSelectedRecord(null);
        setRefreshKey((prev) => prev + 1);
    };

    const handleEdit = (record: DemographicRecord) => {
        setLevel(record.level);
        setSelectedRecord(record);
        setIsFormOpen(true);
        setScope({
            regionCode: record.parent_region_code ?? '',
            provinceCode: record.parent_province_code ?? '',
            cityCode: record.parent_city_code ?? '',
        });
    };

    const handleScopeChange = (nextScope: Partial<DemographicScope>) => {
        setScope((current) => {
            const merged = { ...current, ...nextScope };

            if (Object.prototype.hasOwnProperty.call(nextScope, 'regionCode')) {
                merged.provinceCode = '';
                merged.cityCode = '';
            }

            if (Object.prototype.hasOwnProperty.call(nextScope, 'provinceCode')) {
                merged.cityCode = '';
            }

            return merged;
        });
    };

    const ActiveIcon = levelIcons[activeLevel.value];

    return (
        <div className="space-y-4 p-4">
            <section className="rounded-3xl border border-emerald-900/10 bg-[radial-gradient(circle_at_top_right,_rgba(110,231,183,0.28),_transparent_32%),linear-gradient(135deg,_#053b36_0%,_#0f5f53_55%,_#147a63_100%)] p-6 text-white shadow-sm">
                <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div className="max-w-3xl space-y-3">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge className="bg-white/12 text-white hover:bg-white/12">Reference Master</Badge>
                            <Badge className="border-white/20 bg-emerald-300/15 text-emerald-50 hover:bg-emerald-300/15">Level-based CRUD</Badge>
                        </div>

                        <div className="space-y-2">
                            <h1 className="text-2xl font-semibold tracking-tight">Demographic hierarchy workspace</h1>
                            <p className="max-w-2xl text-sm text-emerald-50/85">
                                Manage region, province, city, and barangay references from one screen. Parent-child filters keep deeper levels scoped so the module stays usable even with the full barangay dataset.
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl border border-white/15 bg-white/10 p-4 backdrop-blur-sm xl:min-w-[280px]">
                        <div className="flex items-center gap-3">
                            <div className="rounded-2xl bg-white/12 p-3">
                                <ActiveIcon className="size-5" />
                            </div>
                            <div>
                                <p className="text-xs font-medium tracking-[0.24em] text-emerald-100/70 uppercase">Active level</p>
                                <p className="text-lg font-semibold">{activeLevel.shortTitle}</p>
                                <p className="text-sm text-emerald-50/80">{activeLevel.levelLabel}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                {DEMOGRAPHIC_LEVELS.map((item) => {
                    const Icon = levelIcons[item.value];
                    const active = item.value === level;

                    return (
                        <button
                            key={item.value}
                            type="button"
                            onClick={() => handleLevelChange(item.value)}
                            className={cn(
                                'rounded-2xl border p-4 text-left shadow-sm transition-colors',
                                active
                                    ? 'border-emerald-500 bg-emerald-50 text-emerald-950'
                                    : 'border-slate-200 bg-white text-slate-900 hover:border-emerald-200 hover:bg-emerald-50/50',
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className={cn('rounded-2xl p-3', active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600')}>
                                    <Icon className="size-5" />
                                </div>
                                <Badge variant={active ? 'default' : 'outline'}>{item.levelLabel}</Badge>
                            </div>

                            <div className="mt-4 space-y-1">
                                <p className="text-base font-semibold">{item.title}</p>
                                <p className={cn('text-sm', active ? 'text-emerald-800' : 'text-slate-500')}>{item.description}</p>
                            </div>
                        </button>
                    );
                })}
            </section>

            <div className={cn('grid gap-4', isFormOpen ? 'xl:grid-cols-[420px_minmax(0,1fr)]' : 'grid-cols-1')}>
                {isFormOpen && <Form level={level} formval={selectedRecord} scope={scope} onCreated={handleCreatedOrUpdated} onCancel={() => setSelectedRecord(null)} />}

                <div className="space-y-4">
                    <div className="flex flex-col gap-3 rounded-2xl border bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
                        <div className="flex items-center gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                size="icon"
                                onClick={() => setIsFormOpen((prev) => !prev)}
                                title={isFormOpen ? 'Hide demographic form' : 'Show demographic form'}
                            >
                                {isFormOpen ? <PanelLeftClose className="size-4" /> : <PanelLeftOpen className="size-4" />}
                            </Button>

                            <div>
                                <p className="text-sm font-medium">
                                    {selectedRecord ? `Editing ${selectedRecord.name}` : `Create ${activeLevel.shortTitle.toLowerCase()}`}
                                </p>
                                <p className="text-muted-foreground text-xs">
                                    {selectedRecord
                                        ? 'Review parent hierarchy, metadata, and active status before saving changes.'
                                        : 'Use the form to register a new demographic reference at the selected level.'}
                                </p>
                            </div>
                        </div>

                        <Button
                            type="button"
                            onClick={() => {
                                setSelectedRecord(null);
                                setIsFormOpen(true);
                            }}
                        >
                            <Plus className="size-4" />
                            New {activeLevel.shortTitle}
                        </Button>
                    </div>

                    <div className="rounded-2xl border border-dashed bg-emerald-50/60 p-4 text-sm text-slate-700">
                        <div className="flex items-start gap-3">
                            <ScrollText className="mt-0.5 size-4 text-emerald-700" />
                            <p>
                                New records are tagged with a `UserLevelID` automatically: region `1`, province `2`, city `3`, barangay `4`. Delete is blocked when a level still has child references below it.
                            </p>
                        </div>
                    </div>

                    <List level={level} refreshKey={refreshKey} scope={scope} onScopeChange={handleScopeChange} onEdit={handleEdit} />
                </div>
            </div>
        </div>
    );
}
