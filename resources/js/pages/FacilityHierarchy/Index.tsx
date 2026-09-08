import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import { ChevronLeft, ChevronRight, Network, Pencil, Plus, RefreshCw, Search, Trash2, X } from 'lucide-react';
import { FormEvent, useCallback, useEffect, useMemo, useState } from 'react';
import { toast } from 'sonner';

type Level = { value: string; label: string };
type Area = { code: string; name: string; region_code?: string };
type Facility = {
    hfhudcode: string;
    facility_name: string;
    region_code: string;
    province_code: string;
    city_code: string;
    bgycode: string;
    region_name: string | null;
    province_name: string | null;
    city_name: string | null;
    barangay_name: string | null;
    facility_type_name: string | null;
    hierarchy: Hierarchy | null;
};
type Hierarchy = {
    id: number;
    facility_hfhudcode: string;
    parent_hfhudcode: string | null;
    level: string;
    referral_network: string;
    coverage_region_code: string;
    coverage_province_code: string | null;
    coverage_notes: string | null;
    is_active: boolean;
    facility?: Facility;
    parent?: { facility?: Facility };
    children_count?: number;
};
type PageData = { data: Hierarchy[]; current_page: number; last_page: number; total: number };
type Props = { canCreate: boolean; canEdit: boolean; canDelete: boolean; levels: Level[]; regions: Area[]; provinces: Area[] };
type ApiError = { response?: { data?: { message?: string; errors?: Record<string, string[]> } } };

const blank = {
    facility_hfhudcode: '',
    parent_hfhudcode: '',
    level: 'lower',
    referral_network: '',
    coverage_region_code: '',
    coverage_province_code: '',
    coverage_notes: '',
    is_active: true,
};
const breadcrumbs = [{ title: 'Facility Hierarchy', href: '/facility-hierarchy' }];
const errorMessage = (error: unknown, fallback: string) => {
    if (!axios.isAxiosError(error)) return fallback;
    const apiError = error as ApiError;
    const validationMessage = Object.values(apiError.response?.data?.errors ?? {})[0]?.[0];
    return validationMessage ?? apiError.response?.data?.message ?? fallback;
};

export default function FacilityHierarchyIndex({ canCreate, canEdit, canDelete, levels, regions, provinces }: Props) {
    const [rows, setRows] = useState<PageData>({ data: [], current_page: 1, last_page: 1, total: 0 });
    const [facilities, setFacilities] = useState<Facility[]>([]);
    const [form, setForm] = useState(blank);
    const [editing, setEditing] = useState<Hierarchy | null>(null);
    const [open, setOpen] = useState(false);
    const [facilityDialogOpen, setFacilityDialogOpen] = useState(false);
    const [facilitySearch, setFacilitySearch] = useState('');
    const [selectedFacilityCodes, setSelectedFacilityCodes] = useState<string[]>([]);
    const [facilityTypeFilter, setFacilityTypeFilter] = useState('');
    const [selectionFilter, setSelectionFilter] = useState('available');
    const [facilitiesLoading, setFacilitiesLoading] = useState(false);
    const [picker, setPicker] = useState({ region: '', province: '', city: '', barangay: '' });
    const [busy, setBusy] = useState(false);
    const [filters, setFilters] = useState({ search: '', level: '', region: '', province: '', page: 1 });

    const load = useCallback(async () => {
        const { data } = await axios.get('/facility-hierarchy/data', { params: { ...filters, per_page: 20 } });
        setRows(data);
    }, [filters]);
    useEffect(() => {
        void load();
    }, [load]);
    useEffect(() => {
        if (!picker.region) {
            setFacilities([]);
            return;
        }

        let active = true;
        setFacilities([]);
        setFacilitiesLoading(true);
        axios
            .get('/facility-hierarchy/options', { params: { region: picker.region } })
            .then(({ data }) => {
                if (active) setFacilities(data.data);
            })
            .catch(() => {
                if (active) {
                    setFacilities([]);
                    toast.error('Unable to load facilities for the selected region.');
                }
            })
            .finally(() => {
                if (active) setFacilitiesLoading(false);
            });

        return () => {
            active = false;
        };
    }, [picker.region]);

    const availableProvinces = useMemo(
        () => provinces.filter((p) => !filters.region || String(p.region_code) === filters.region),
        [provinces, filters.region],
    );
    const selectedFacilities = facilities.filter((facility) => selectedFacilityCodes.includes(facility.hfhudcode));
    const selectedFacility = selectedFacilities[0] ?? facilities.find((facility) => facility.hfhudcode === form.facility_hfhudcode);
    const parentLevel = levels.findIndex((level) => level.value === form.level) - 1;
    const parentOptions = facilities.filter((facility) => {
        const isEligibleParent =
            facility.hierarchy?.level === levels[parentLevel]?.value &&
            facility.hierarchy?.is_active === true &&
            facility.hfhudcode !== form.facility_hfhudcode;

        if (!isEligibleParent || !selectedFacility) return isEligibleParent;
        if (String(facility.region_code) !== String(selectedFacility.region_code)) return false;

        return parentLevel < 1 || String(facility.province_code) === String(selectedFacility.province_code);
    });
    const uniqueLocations = (items: Facility[], code: keyof Facility, name: keyof Facility) =>
        Array.from(new Map(items.filter((item) => item[code]).map((item) => [String(item[code]), String(item[name] ?? item[code])])).entries()).map(
            ([value, label]) => ({ value, label }),
        );
    // The API is already scoped to the selected region. Avoid comparing legacy
    // numeric facility codes (1) with canonical reference codes (01).
    const regionFacilities = picker.region ? facilities : [];
    const regionHospitalFacilities = regionFacilities.filter((facility) => (facility.facility_type_name ?? '').toLowerCase().includes('hospital'));
    const levelFacilities = form.level === 'apex' ? regionHospitalFacilities : regionFacilities;
    const pickerProvinces = uniqueLocations(levelFacilities, 'province_code', 'province_name');
    const provinceFacilities = levelFacilities.filter((facility) => String(facility.province_code) === picker.province);
    const pickerCities = uniqueLocations(provinceFacilities, 'city_code', 'city_name');
    const cityFacilities = provinceFacilities.filter((facility) => String(facility.city_code) === picker.city);
    const pickerBarangays = uniqueLocations(cityFacilities, 'bgycode', 'barangay_name');
    const barangayFacilities = cityFacilities.filter((facility) => String(facility.bgycode) === picker.barangay);
    const facilitiesInCurrentScope = !picker.region
        ? []
        : picker.barangay
          ? barangayFacilities
          : picker.city
            ? cityFacilities
            : picker.province
              ? provinceFacilities
              : levelFacilities;
    const facilityTypes = Array.from(
        new Set(facilitiesInCurrentScope.map((facility) => facility.facility_type_name).filter((type): type is string => Boolean(type))),
    ).sort((left, right) => left.localeCompare(right));
    const displayedFacilities = facilitiesInCurrentScope.filter((facility) => {
        const search = facilitySearch.trim().toLowerCase();
        const isSelected = selectedFacilityCodes.includes(facility.hfhudcode);
        const matchesSelection =
            selectionFilter === 'all' ||
            (selectionFilter === 'selected' && isSelected) ||
            (selectionFilter === 'available' && !facility.hierarchy) ||
            (selectionFilter === 'mapped' && Boolean(facility.hierarchy));
        return (
            (!facilityTypeFilter || facility.facility_type_name === facilityTypeFilter) &&
            matchesSelection &&
            (!search ||
                [facility.facility_name, facility.hfhudcode, facility.facility_type_name, facility.barangay_name].some((value) =>
                    value?.toLowerCase().includes(search),
                ))
        );
    });
    const visibleAvailableFacilities = displayedFacilities.filter((facility) => !facility.hierarchy);
    const startCreate = () => {
        setEditing(null);
        setForm(blank);
        setSelectedFacilityCodes([]);
        setPicker({ region: '', province: '', city: '', barangay: '' });
        setFacilitySearch('');
        setFacilityTypeFilter('');
        setSelectionFilter('available');
        setOpen(true);
    };
    const startEdit = (row: Hierarchy) => {
        setEditing(row);
        setForm({
            facility_hfhudcode: row.facility_hfhudcode,
            parent_hfhudcode: row.parent_hfhudcode ?? '',
            level: row.level,
            referral_network: row.referral_network,
            coverage_region_code: row.coverage_region_code,
            coverage_province_code: row.coverage_province_code ?? '',
            coverage_notes: row.coverage_notes ?? '',
            is_active: row.is_active,
        });
        setSelectedFacilityCodes([row.facility_hfhudcode]);
        setPicker({ region: row.coverage_region_code, province: '', city: '', barangay: '' });
        setOpen(true);
    };
    const toggleFacility = (facility: Facility) => {
        if (facility.hierarchy) return;

        setSelectedFacilityCodes((current) => {
            if (current.includes(facility.hfhudcode)) return current.filter((code) => code !== facility.hfhudcode);

            const first = facilities.find((item) => item.hfhudcode === current[0]);
            const requiresSameProvince = ['district', 'lower'].includes(form.level);
            if (first && requiresSameProvince && String(first.province_code) !== String(facility.province_code)) {
                toast.error('Facilities assigned together at this level must be in the same province.');
                return current;
            }

            return [...current, facility.hfhudcode];
        });
    };
    const applyFacilitySelection = () => {
        const facility = facilities.find((item) => item.hfhudcode === selectedFacilityCodes[0]);
        if (!facility) return;

        setForm((current) => ({
            ...current,
            facility_hfhudcode: facility.hfhudcode,
            parent_hfhudcode: '',
            referral_network: current.level === 'apex' && facility ? `${facility.facility_name} Referral Network` : '',
            coverage_region_code: facility?.region_code ? String(facility.region_code) : '',
            coverage_province_code: facility?.province_code ? String(facility.province_code) : '',
        }));
        setFacilityDialogOpen(false);
    };
    const selectVisibleFacilities = () => {
        if (['district', 'lower'].includes(form.level) && !picker.province) {
            toast.error('Select a province before selecting multiple facilities at this hierarchy level.');
            return;
        }

        setSelectedFacilityCodes((current) =>
            Array.from(new Set([...current, ...visibleAvailableFacilities.map((facility) => facility.hfhudcode)])).slice(0, 200),
        );
    };
    const chooseParent = (code: string) => {
        const parent = facilities.find((item) => item.hfhudcode === code)?.hierarchy;
        setForm((current) => ({ ...current, parent_hfhudcode: code, referral_network: parent?.referral_network ?? current.referral_network }));
    };
    const submit = async (event: FormEvent) => {
        event.preventDefault();
        setBusy(true);
        try {
            const payload = {
                ...form,
                parent_hfhudcode: form.level === 'apex' ? null : form.parent_hfhudcode || null,
                coverage_province_code: form.coverage_province_code || null,
            };
            if (editing) {
                await axios.put(`/facility-hierarchy/${editing.id}`, payload);
            } else {
                await axios.post('/facility-hierarchy/bulk', {
                    facility_hfhudcodes: selectedFacilityCodes,
                    parent_hfhudcode: payload.parent_hfhudcode,
                    level: payload.level,
                    referral_network: payload.referral_network,
                    coverage_notes: payload.coverage_notes,
                    is_active: payload.is_active,
                });
            }
            toast.success(editing ? 'Hierarchy mapping updated.' : `${selectedFacilityCodes.length} hierarchy mapping(s) created.`);
            setOpen(false);
            await load();
            const { data } = await axios.get('/facility-hierarchy/options', { params: { region: form.coverage_region_code } });
            setFacilities(data.data);
        } catch (error: unknown) {
            toast.error(errorMessage(error, 'Unable to save mapping.'));
        } finally {
            setBusy(false);
        }
    };
    const remove = async (row: Hierarchy) => {
        if (!window.confirm(`Remove ${row.facility?.facility_name} from the hierarchy?`)) return;
        try {
            await axios.delete(`/facility-hierarchy/${row.id}`);
            toast.success('Mapping removed.');
            await load();
        } catch (error: unknown) {
            toast.error(errorMessage(error, 'Unable to remove mapping.'));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Facility Hierarchy" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-primary text-sm font-medium">Referral Network</p>
                        <h1 className="text-2xl font-semibold">Facility Hierarchy Management</h1>
                        <p className="text-muted-foreground text-sm">
                            Map facilities from apex hospitals through provincial, district, and lower-level care.
                        </p>
                    </div>
                    {canCreate && (
                        <Button onClick={startCreate}>
                            <Plus className="size-4" /> Add Mapping
                        </Button>
                    )}
                </div>
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    {levels.map((level) => (
                        <Card key={level.value}>
                            <CardContent className="p-4">
                                <p className="text-muted-foreground text-xs">{level.label}</p>
                                <p className="mt-1 text-2xl font-semibold tabular-nums">
                                    {rows.data.filter((row) => row.level === level.value).length}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                    <Card>
                        <CardContent className="p-4">
                            <p className="text-muted-foreground text-xs">Total mappings</p>
                            <p className="mt-1 text-2xl font-semibold tabular-nums">{rows.total}</p>
                        </CardContent>
                    </Card>
                </div>
                {open && (
                    <Card>
                        <CardHeader className="flex-row items-start justify-between">
                            <div>
                                <CardTitle>{editing ? 'Edit hierarchy mapping' : 'Add hierarchy mapping'}</CardTitle>
                                <CardDescription>
                                    Facility geography is taken from the facility directory. Child networks inherit from their parent.
                                </CardDescription>
                            </div>
                            <Button variant="ghost" size="icon" onClick={() => setOpen(false)}>
                                <X className="size-4" />
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submit} className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                <div className="space-y-1">
                                    <Label>Hierarchy level</Label>
                                    <select
                                        className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                        value={form.level}
                                        onChange={(event) => {
                                            setSelectedFacilityCodes([]);
                                            setFacilityTypeFilter('');
                                            setPicker((current) => ({ ...current, province: '', city: '', barangay: '' }));
                                            setForm({
                                                ...form,
                                                level: event.target.value,
                                                facility_hfhudcode: '',
                                                parent_hfhudcode: '',
                                                referral_network: '',
                                            });
                                        }}
                                    >
                                        {levels.map((level) => (
                                            <option key={level.value} value={level.value}>
                                                {level.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label>{editing ? 'Facility' : 'Facilities'}</Label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full justify-between font-normal"
                                        disabled={Boolean(editing)}
                                        onClick={() => setFacilityDialogOpen(true)}
                                    >
                                        <span className="truncate">
                                            {selectedFacility
                                                ? selectedFacilityCodes.length > 1
                                                    ? `${selectedFacilityCodes.length} facilities selected`
                                                    : `${selectedFacility.facility_name} (${selectedFacility.hfhudcode})`
                                                : 'Browse facilities by location'}
                                        </span>
                                        <Search className="size-4 shrink-0" />
                                    </Button>
                                    {selectedFacility && (
                                        <p className="text-muted-foreground truncate text-xs">
                                            {selectedFacility.region_name} / {selectedFacility.province_name} / {selectedFacility.city_name}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-1">
                                    <Label>Parent facility</Label>
                                    <select
                                        className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                        required={form.level !== 'apex'}
                                        disabled={form.level === 'apex'}
                                        value={form.parent_hfhudcode}
                                        onChange={(e) => chooseParent(e.target.value)}
                                    >
                                        <option value="">{form.level === 'apex' ? 'No parent (top level)' : 'Select parent'}</option>
                                        {parentOptions.map((f) => (
                                            <option key={f.hfhudcode} value={f.hfhudcode}>
                                                {f.facility_name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Referral network</Label>
                                    <Input
                                        required
                                        maxLength={120}
                                        readOnly={form.level !== 'apex' && Boolean(form.parent_hfhudcode)}
                                        value={form.referral_network}
                                        onChange={(e) => setForm({ ...form, referral_network: e.target.value })}
                                        placeholder="e.g. Central Visayas Network"
                                    />
                                </div>
                                <div className="space-y-1">
                                    <Label>Coverage region</Label>
                                    <select
                                        className="border-input bg-muted h-9 w-full rounded-md border px-3 text-sm"
                                        value={form.coverage_region_code}
                                        disabled
                                    >
                                        <option value="">Select a facility</option>
                                        {regions.map((r) => (
                                            <option key={r.code} value={String(r.code)}>
                                                {r.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1">
                                    <Label>Coverage province</Label>
                                    <select
                                        className="border-input bg-muted h-9 w-full rounded-md border px-3 text-sm"
                                        value={form.coverage_province_code}
                                        disabled
                                    >
                                        <option value="">Region-wide / not specified</option>
                                        {provinces.map((p) => (
                                            <option key={p.code} value={p.code}>
                                                {p.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="space-y-1 xl:col-span-2">
                                    <Label>Coverage notes</Label>
                                    <Input
                                        maxLength={1000}
                                        value={form.coverage_notes}
                                        onChange={(e) => setForm({ ...form, coverage_notes: e.target.value })}
                                        placeholder="Cities, municipalities, catchment area, or coordination notes"
                                    />
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.is_active}
                                        onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
                                    />{' '}
                                    Active routing node
                                </label>
                                <div className="flex justify-end gap-2 md:col-span-2 xl:col-span-3">
                                    <Button type="button" variant="outline" onClick={() => setOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button disabled={busy || !selectedFacility}>
                                        {busy ? 'Saving...' : editing ? 'Save Mapping' : `Save ${selectedFacilityCodes.length} Mapping(s)`}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
                <Dialog open={facilityDialogOpen} onOpenChange={setFacilityDialogOpen}>
                    <DialogContent className="flex max-h-[90vh] flex-col sm:max-w-5xl">
                        <DialogHeader>
                            <DialogTitle>Select healthcare facility</DialogTitle>
                            <DialogDescription>
                                Select a region, then optionally narrow by province, city or municipality, and barangay.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div className="space-y-1">
                                <Label>1. Region</Label>
                                <select
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    value={picker.region}
                                    onChange={(event) => {
                                        setSelectedFacilityCodes([]);
                                        setFacilityTypeFilter('');
                                        setPicker({ region: event.target.value, province: '', city: '', barangay: '' });
                                    }}
                                >
                                    <option value="">Select region</option>
                                    {regions.map((region) => (
                                        <option key={region.code} value={String(region.code)}>
                                            {region.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-1">
                                <Label>2. Province</Label>
                                <select
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    value={picker.province}
                                    disabled={!picker.region}
                                    onChange={(event) =>
                                        setPicker((current) => ({ ...current, province: event.target.value, city: '', barangay: '' }))
                                    }
                                >
                                    <option value="">Select province</option>
                                    {pickerProvinces.map((province) => (
                                        <option key={province.value} value={province.value}>
                                            {province.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-1">
                                <Label>3. City/Municipality</Label>
                                <select
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    value={picker.city}
                                    disabled={!picker.province}
                                    onChange={(event) => setPicker((current) => ({ ...current, city: event.target.value, barangay: '' }))}
                                >
                                    <option value="">Select city/municipality</option>
                                    {pickerCities.map((city) => (
                                        <option key={city.value} value={city.value}>
                                            {city.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div className="space-y-1">
                                <Label>4. Barangay</Label>
                                <select
                                    className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                    value={picker.barangay}
                                    disabled={!picker.city}
                                    onChange={(event) => setPicker((current) => ({ ...current, barangay: event.target.value }))}
                                >
                                    <option value="">Select barangay</option>
                                    {pickerBarangays.map((barangay) => (
                                        <option key={barangay.value} value={barangay.value}>
                                            {barangay.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-2.5 left-3 size-4" />
                            <Input
                                className="pl-9"
                                value={facilitySearch}
                                onChange={(event) => setFacilitySearch(event.target.value)}
                                placeholder="Search facility or HFHUD code"
                            />
                        </div>
                        <div className="grid gap-3 sm:grid-cols-2">
                            <select
                                className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                value={facilityTypeFilter}
                                onChange={(event) => setFacilityTypeFilter(event.target.value)}
                            >
                                <option value="">All facility types</option>
                                {facilityTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {type}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="border-input h-9 w-full rounded-md border bg-transparent px-3 text-sm"
                                value={selectionFilter}
                                onChange={(event) => setSelectionFilter(event.target.value)}
                            >
                                <option value="available">Available only</option>
                                <option value="selected">Selected only</option>
                                <option value="mapped">Already mapped</option>
                                <option value="all">All facilities</option>
                            </select>
                        </div>
                        <div className="min-h-0 flex-1 overflow-y-auto rounded-md border">
                            <div className="bg-muted/60 sticky top-0 grid grid-cols-[minmax(0,1fr)_minmax(8rem,0.35fr)_auto] gap-3 border-b px-3 py-2 text-xs font-semibold">
                                <span>Healthcare facility</span>
                                <span>Type</span>
                                <span>Action</span>
                            </div>
                            {displayedFacilities.map((facility) => (
                                <div
                                    key={facility.hfhudcode}
                                    className="grid grid-cols-[minmax(0,1fr)_minmax(8rem,0.35fr)_auto] items-center gap-3 border-b px-3 py-2 last:border-b-0"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate text-sm font-medium">{facility.facility_name}</p>
                                        <p className="text-muted-foreground text-xs">
                                            HFHUD: {facility.hfhudcode} · {facility.barangay_name ?? 'No barangay'}
                                        </p>
                                    </div>
                                    <span className="truncate text-xs">{facility.facility_type_name ?? 'Health Facility'}</span>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant={selectedFacilityCodes.includes(facility.hfhudcode) ? 'default' : 'outline'}
                                        disabled={Boolean(facility.hierarchy)}
                                        onClick={() => toggleFacility(facility)}
                                    >
                                        {facility.hierarchy ? 'Mapped' : selectedFacilityCodes.includes(facility.hfhudcode) ? 'Selected' : 'Select'}
                                    </Button>
                                </div>
                            ))}
                            {facilitiesLoading && <div className="text-muted-foreground py-12 text-center text-sm">Loading facilities...</div>}
                            {!facilitiesLoading && !displayedFacilities.length && (
                                <div className="text-muted-foreground rounded-md border border-dashed py-12 text-center text-sm">
                                    {picker.region
                                        ? 'No health facilities match the current location and search.'
                                        : 'Select a region to show its facilities.'}
                                </div>
                            )}
                        </div>
                        <div className="flex items-center justify-between gap-3">
                            <span className="text-muted-foreground text-sm">{selectedFacilityCodes.length} selected (maximum 200)</span>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={!visibleAvailableFacilities.length}
                                    onClick={selectVisibleFacilities}
                                >
                                    Select visible
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={!selectedFacilityCodes.length}
                                    onClick={() => setSelectedFacilityCodes([])}
                                >
                                    Clear
                                </Button>
                                <Button type="button" disabled={!selectedFacilityCodes.length} onClick={applyFacilitySelection}>
                                    Use selected facilities
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Network className="size-5" /> Referral hierarchy
                        </CardTitle>
                        <CardDescription>Filter by geography or level to review routing responsibility and network coverage.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-5">
                            <Input
                                placeholder="Search facility, code, network"
                                value={filters.search}
                                onChange={(e) => setFilters({ ...filters, search: e.target.value, page: 1 })}
                            />
                            <select
                                className="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                                value={filters.level}
                                onChange={(e) => setFilters({ ...filters, level: e.target.value, page: 1 })}
                            >
                                <option value="">All levels</option>
                                {levels.map((l) => (
                                    <option key={l.value} value={l.value}>
                                        {l.label}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                                value={filters.region}
                                onChange={(e) => setFilters({ ...filters, region: e.target.value, province: '', page: 1 })}
                            >
                                <option value="">All regions</option>
                                {regions.map((r) => (
                                    <option key={r.code} value={String(r.code)}>
                                        {r.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                className="border-input h-9 rounded-md border bg-transparent px-3 text-sm"
                                value={filters.province}
                                onChange={(e) => setFilters({ ...filters, province: e.target.value, page: 1 })}
                            >
                                <option value="">All provinces</option>
                                {availableProvinces.map((p) => (
                                    <option key={p.code} value={p.code}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                            <Button variant="outline" onClick={() => void load()}>
                                <RefreshCw className="size-4" /> Refresh
                            </Button>
                        </div>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Facility</TableHead>
                                        <TableHead>Level</TableHead>
                                        <TableHead>Parent / referral path</TableHead>
                                        <TableHead>Network</TableHead>
                                        <TableHead>Coverage</TableHead>
                                        <TableHead>Children</TableHead>
                                        <TableHead>Status</TableHead>
                                        {(canEdit || canDelete) && <TableHead className="text-right">Actions</TableHead>}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {rows.data.map((row) => (
                                        <TableRow key={row.id}>
                                            <TableCell className="whitespace-nowrap">
                                                <p className="font-medium">{row.facility?.facility_name}</p>
                                                <p className="text-muted-foreground text-xs">{row.facility_hfhudcode}</p>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">{levels.find((l) => l.value === row.level)?.label}</TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {row.parent?.facility?.facility_name ?? 'Top-level destination'}
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap">{row.referral_network}</TableCell>
                                            <TableCell className="whitespace-nowrap">
                                                {regions.find((r) => String(r.code) === String(row.coverage_region_code))?.name ??
                                                    row.coverage_region_code}
                                                {row.coverage_province_code
                                                    ? ` / ${provinces.find((p) => p.code === row.coverage_province_code)?.name ?? row.coverage_province_code}`
                                                    : ''}
                                            </TableCell>
                                            <TableCell>{row.children_count ?? 0}</TableCell>
                                            <TableCell>
                                                <Badge variant={row.is_active ? 'default' : 'secondary'}>
                                                    {row.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            {(canEdit || canDelete) && (
                                                <TableCell>
                                                    <div className="flex justify-end gap-1">
                                                        {canEdit && (
                                                            <Button size="icon" variant="ghost" onClick={() => startEdit(row)} title="Edit">
                                                                <Pencil className="size-4" />
                                                            </Button>
                                                        )}
                                                        {canDelete && (
                                                            <Button size="icon" variant="ghost" onClick={() => void remove(row)} title="Delete">
                                                                <Trash2 className="size-4 text-red-600" />
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    ))}
                                    {!rows.data.length && (
                                        <TableRow>
                                            <TableCell colSpan={canEdit || canDelete ? 8 : 7} className="text-muted-foreground h-24 text-center">
                                                No hierarchy mappings match the filters.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-muted-foreground">{rows.total} mapping(s)</span>
                            <div className="flex items-center gap-2">
                                <Button
                                    size="icon"
                                    variant="outline"
                                    disabled={rows.current_page <= 1}
                                    onClick={() => setFilters({ ...filters, page: filters.page - 1 })}
                                >
                                    <ChevronLeft className="size-4" />
                                </Button>
                                <span>
                                    Page {rows.current_page} of {rows.last_page}
                                </span>
                                <Button
                                    size="icon"
                                    variant="outline"
                                    disabled={rows.current_page >= rows.last_page}
                                    onClick={() => setFilters({ ...filters, page: filters.page + 1 })}
                                >
                                    <ChevronRight className="size-4" />
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
