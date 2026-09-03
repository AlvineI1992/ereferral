import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Link, router } from '@inertiajs/react';
import axios from 'axios';
import { ArrowRightIcon, ChevronDown, Hospital, List, Mars, Plus, Printer, SlidersHorizontal, Trash2, Venus, X } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';
import IncomingDashboard from './IncomingDashboard';
import type { IncomingAdvancedFilters, IncomingFilterOptions, IncomingReferralRow, IncomingSummary, PermissionProps } from './types';

const emptySummary: IncomingSummary = {
    totalIncoming: 0,
    todayIncoming: 0,
    emergencyCount: 0,
    outpatientCount: 0,
    receivingFacilities: 0,
    topReasons: [],
    topProvinces: [],
    topCities: [],
    topBarangays: [],
    generatedAt: '',
};

const emptyFilterOptions: IncomingFilterOptions = {
    origins: [],
    destinations: [],
    types: [],
    reasons: [],
};

const emptyAdvancedFilters: IncomingAdvancedFilters = {
    date_from: '',
    date_to: '',
    origin: '',
    destination: '',
    type: '',
    category: '',
    reason: '',
};

const Lists = ({ canCreate, canDelete, refreshKey, onEdit }: PermissionProps) => {
    const [data, setData] = useState<IncomingReferralRow[]>([]);
    const [summary, setSummary] = useState<IncomingSummary>(emptySummary);
    const [searchTerm, setSearchTerm] = useState('');
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalRows, setTotalRows] = useState(0);
    const [perPage, setPerPage] = useState(5);
    const [filterOptions, setFilterOptions] = useState<IncomingFilterOptions>(emptyFilterOptions);
    const [advancedFilters, setAdvancedFilters] = useState<IncomingAdvancedFilters>(emptyAdvancedFilters);
    const [appliedFilters, setAppliedFilters] = useState<IncomingAdvancedFilters>(emptyAdvancedFilters);
    const [advancedOpen, setAdvancedOpen] = useState(false);
    const [filterError, setFilterError] = useState('');
    const [refreshTick, setRefreshTick] = useState(0);
    const [deleteTarget, setDeleteTarget] = useState<IncomingReferralRow | null>(null);
    const [deleteConfirmation, setDeleteConfirmation] = useState('');
    const [deleting, setDeleting] = useState(false);
    const [deleteError, setDeleteError] = useState('');

    const handleGoto = (id: string) => {
        if (!id) return;
        const encodedId = btoa(id.toString());
        router.visit(`/incoming/profile/${encodedId}`);
    };

    useEffect(() => {
        const delayDebounce = setTimeout(() => {
            const fetchData = async () => {
                setLoading(true);
                try {
                    const response = await axios.get('/incoming/list', {
                        params: {
                            page,
                            per_page: perPage,
                            search: searchTerm || undefined,
                            ...Object.fromEntries(Object.entries(appliedFilters).filter(([, value]) => value !== '')),
                        },
                    });
                    setData(response.data.data ?? []);
                    setTotalRows(response.data.total ?? 0);
                    setSummary(response.data.summary ?? emptySummary);
                    setFilterOptions(response.data.filter_options ?? emptyFilterOptions);
                } catch (error) {
                    console.error('Error fetching referrals:', error);
                } finally {
                    setLoading(false);
                }
            };

            void fetchData();
        }, 500);

        return () => clearTimeout(delayDebounce);
    }, [refreshKey, refreshTick, page, searchTerm, perPage, appliedFilters]);

    const closeDeleteDialog = () => {
        if (deleting) return;
        setDeleteTarget(null);
        setDeleteConfirmation('');
        setDeleteError('');
    };

    const deleteReferral = async () => {
        if (!deleteTarget || deleteConfirmation !== deleteTarget.LogID) return;

        setDeleting(true);
        setDeleteError('');

        try {
            await axios.delete(`/referrals/${encodeURIComponent(deleteTarget.LogID)}`);
            setDeleteTarget(null);
            setDeleteConfirmation('');
            setPage((current) => (data.length === 1 && current > 1 ? current - 1 : current));
            setRefreshTick((current) => current + 1);
        } catch (error) {
            setDeleteError(
                axios.isAxiosError(error)
                    ? (error.response?.data?.message ?? 'Unable to delete this referral transaction.')
                    : 'Unable to delete this referral transaction.',
            );
        } finally {
            setDeleting(false);
        }
    };

    const handleEdit = (row: IncomingReferralRow) => {
        onEdit?.(row);
    };

    const totalPages = Math.ceil(totalRows / perPage);
    const activeFilterCount = Object.values(appliedFilters).filter(Boolean).length;
    const draftFilterCount = Object.values(advancedFilters).filter(Boolean).length;

    const updateAdvancedFilter = (key: keyof IncomingAdvancedFilters, value: string) => {
        setAdvancedFilters((current) => ({ ...current, [key]: value }));
        setFilterError('');
    };

    const applyAdvancedFilters = () => {
        if (advancedFilters.date_from && advancedFilters.date_to && advancedFilters.date_from > advancedFilters.date_to) {
            setFilterError('Date from must be on or before date to.');
            return;
        }

        setPage(1);
        setAppliedFilters({ ...advancedFilters });
        setFilterError('');
    };

    const clearAdvancedFilters = () => {
        setPage(1);
        setAdvancedFilters(emptyAdvancedFilters);
        setAppliedFilters(emptyAdvancedFilters);
        setFilterError('');
    };

    return (
        <div className="flex w-full flex-col gap-3">
            <IncomingDashboard summary={summary} canCreate={!!canCreate} />

            <div className="w-full overflow-x-auto rounded-xl border border-slate-200/80 bg-white/90 p-3 shadow-sm">
                <div className="mb-2 flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                        <List size={20} />
                        <h2 className="text-xl font-semibold tracking-tight">Incoming Referrals</h2>
                    </div>
                    {canCreate && (
                        <Link href="/referrals/create">
                            <Button variant="outline">
                                <Plus className="mr-2" /> Add Referral
                            </Button>
                        </Link>
                    )}
                </div>

                <div className="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div className="flex items-center gap-1">
                        <label htmlFor="perPage">Rows per page:</label>
                        <select
                            id="perPage"
                            value={perPage}
                            onChange={(e) => {
                                setPage(1);
                                setPerPage(Number(e.target.value));
                            }}
                            className="border px-2 py-1 text-xs"
                        >
                            {[5, 10, 15, 25, 50].map((num) => (
                                <option key={num} value={num}>
                                    {num}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex w-full items-center gap-2 sm:w-auto">
                        <Input
                            type="search"
                            placeholder="Search patient..."
                            value={searchTerm}
                            onChange={(e) => {
                                setPage(1);
                                setSearchTerm(e.target.value);
                            }}
                            className="min-w-0 flex-1 text-sm sm:w-64"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="shrink-0"
                            aria-expanded={advancedOpen}
                            aria-controls="incoming-advanced-filters"
                            onClick={() => setAdvancedOpen((open) => !open)}
                        >
                            <SlidersHorizontal className="size-4" />
                            Advanced filters
                            {activeFilterCount > 0 && (
                                <span className="bg-primary text-primary-foreground rounded-full px-1.5 text-[10px]">{activeFilterCount}</span>
                            )}
                            <ChevronDown className={`size-4 transition-transform ${advancedOpen ? 'rotate-180' : ''}`} />
                        </Button>
                    </div>
                </div>

                {advancedOpen && (
                    <div id="incoming-advanced-filters" className="mb-3 rounded-lg border border-slate-200 bg-slate-50/70 p-3">
                        <div className="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                            <FilterField label="Date from">
                                <Input
                                    type="date"
                                    value={advancedFilters.date_from}
                                    max={advancedFilters.date_to || undefined}
                                    onChange={(event) => updateAdvancedFilter('date_from', event.target.value)}
                                    className="h-8 text-xs"
                                />
                            </FilterField>
                            <FilterField label="Date to">
                                <Input
                                    type="date"
                                    value={advancedFilters.date_to}
                                    min={advancedFilters.date_from || undefined}
                                    onChange={(event) => updateAdvancedFilter('date_to', event.target.value)}
                                    className="h-8 text-xs"
                                />
                            </FilterField>
                            <FilterField label="Origin">
                                <FilterSelect value={advancedFilters.origin} onChange={(value) => updateAdvancedFilter('origin', value)}>
                                    <option value="">All origins</option>
                                    {filterOptions.origins.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.name}
                                        </option>
                                    ))}
                                </FilterSelect>
                            </FilterField>
                            <FilterField label="Destination">
                                <FilterSelect value={advancedFilters.destination} onChange={(value) => updateAdvancedFilter('destination', value)}>
                                    <option value="">All destinations</option>
                                    {filterOptions.destinations.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.name}
                                        </option>
                                    ))}
                                </FilterSelect>
                            </FilterField>
                            <FilterField label="Referral type">
                                <FilterSelect value={advancedFilters.type} onChange={(value) => updateAdvancedFilter('type', value)}>
                                    <option value="">All types</option>
                                    {filterOptions.types.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.description}
                                        </option>
                                    ))}
                                </FilterSelect>
                            </FilterField>
                            <FilterField label="Category">
                                <FilterSelect value={advancedFilters.category} onChange={(value) => updateAdvancedFilter('category', value)}>
                                    <option value="">All categories</option>
                                    <option value="ER">Emergency</option>
                                    <option value="OPD">Outpatient</option>
                                </FilterSelect>
                            </FilterField>
                            <FilterField label="Reason">
                                <FilterSelect value={advancedFilters.reason} onChange={(value) => updateAdvancedFilter('reason', value)}>
                                    <option value="">All reasons</option>
                                    {filterOptions.reasons.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.description}
                                        </option>
                                    ))}
                                </FilterSelect>
                            </FilterField>
                        </div>

                        <div className="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-xs text-red-600" role="alert">
                                {filterError}
                            </p>
                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={clearAdvancedFilters}
                                    disabled={activeFilterCount === 0 && draftFilterCount === 0}
                                >
                                    <X className="size-4" /> Clear
                                </Button>
                                <Button type="button" size="sm" onClick={applyAdvancedFilters} disabled={loading}>
                                    Apply filters
                                </Button>
                            </div>
                        </div>
                    </div>
                )}

                {/* Table Display */}
                {loading ? (
                    <div className="flex items-center justify-center py-8">
                        <div className="h-6 w-6 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                        <span className="ml-2 text-sm text-blue-600">Loading referrals...</span>
                    </div>
                ) : (
                    <>
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="mb-1 border-t">
                                    <th className="w-12 px-1 py-2 text-left">#</th>
                                    <th className="px-1 py-2 text-left">Patient</th>
                                    <th className="px-1 py-2 text-left">LogID</th>
                                    <th className="px-1 py-2 text-left">Referral Date</th>
                                    <th className="px-1 py-2 text-left">Origin</th>
                                    <th className="px-1 py-2 text-left">Destination</th>
                                    <th className="px-1 py-2 text-left">Type</th>
                                    <th className="px-1 py-2 text-left">Category</th>
                                    <th className="px-1 py-2 text-left">Reason</th>
                                    <th className="px-1 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {data.length > 0 ? (
                                    data.map((row) => (
                                        <tr key={row.LogID}>
                                            <td className="px-1 py-2 align-top font-medium text-slate-600">{row.index}</td>
                                            <td className="px-1 py-2">
                                                <div className="flex flex-col items-start gap-1">
                                                    <div className="flex items-center gap-1">
                                                        <Avatar className="h-8 w-8">
                                                            <AvatarImage src={row.avatar || '/default-avatar.jpg'} />
                                                            <AvatarFallback>
                                                                {row.patient_name?.charAt(0).toUpperCase()}
                                                                {row.patient_name?.charAt(1).toUpperCase()}
                                                            </AvatarFallback>
                                                        </Avatar>
                                                        <span className="text-sm">{row.patient_name}</span>
                                                    </div>
                                                    <div className="ml-10 space-y-1 text-[10px]">
                                                        <div className="flex items-center gap-1">
                                                            <strong>Sex:</strong>
                                                            {row.patient_sex === 'Male' ? (
                                                                <Mars className="text-blue-700" size={12} />
                                                            ) : (
                                                                <Venus className="text-pink-700" size={12} />
                                                            )}
                                                            <span className={`${row.patient_sex === 'Male' ? 'text-blue-700' : 'text-pink-700'}`}>
                                                                {row.patient_sex}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <strong>Date of birth:</strong> {row.patient_birthdate}
                                                        </div>
                                                        <div>
                                                            <strong>Civil status:</strong> {row.patient_civilstatus}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-1 py-2">{row.LogID}</td>
                                            <td className="px-1 py-2">
                                                {row.referral_date} {row.referral_time}
                                            </td>
                                            <td className="px-1 py-2">
                                                <div className="flex items-center gap-1">
                                                    <Hospital size={12} />
                                                    <span className="text-[10px]">{row.referral_origin_name}</span>
                                                </div>
                                            </td>
                                            <td className="px-1 py-2">
                                                <div className="flex items-center gap-1">
                                                    <Hospital size={12} />
                                                    <span className="text-[10px]">{row.referral_destination_name}</span>
                                                </div>
                                            </td>
                                            <td className="px-1 py-2 text-[10px]">{row.referral_type_description}</td>
                                            <td className="px-1 py-2 text-[10px]">{row.referral_category}</td>
                                            <td className="px-1 py-2 text-[10px]">{row.referral_reason_description}</td>
                                            <td className="px-1 py-2">
                                                <div className="flex justify-center gap-1">
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        onClick={() => handleEdit(row)}
                                                        className="group hover:bg-green-500"
                                                    >
                                                        <Printer size={16} className="text-blue-700 group-hover:text-white" />
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="icon"
                                                        onClick={() => handleGoto(row.LogID)}
                                                        className="group hover:bg-green-700"
                                                    >
                                                        <ArrowRightIcon size={16} className="text-blue-700 group-hover:text-white" />
                                                    </Button>
                                                    {canDelete && (
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => setDeleteTarget(row)}
                                                            className="group hover:border-red-600 hover:bg-red-600"
                                                            aria-label={`Delete referral ${row.LogID}`}
                                                        >
                                                            <Trash2 size={16} className="text-red-600 group-hover:text-white" />
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={10} className="py-6 text-center text-gray-500 italic">
                                            No referrals found.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        <div className="mt-4 flex items-center justify-between text-xs text-gray-600">
                            <span>
                                Page <strong>{page}</strong> of <strong>{totalPages}</strong> ({totalRows} total)
                            </span>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    disabled={page <= 1}
                                    onClick={() => setPage((p) => Math.max(p - 1, 1))}
                                    className="px-2 py-1 text-xs"
                                >
                                    Previous
                                </Button>
                                {Array.from({ length: totalPages }, (_, i) => i + 1)
                                    .slice(Math.max(0, page - 3), Math.min(totalPages, page + 2))
                                    .map((pNum) => (
                                        <Button
                                            key={pNum}
                                            variant={pNum === page ? 'default' : 'outline'}
                                            className="px-3 py-1 text-xs"
                                            onClick={() => setPage(pNum)}
                                        >
                                            {pNum}
                                        </Button>
                                    ))}
                                <Button
                                    variant="outline"
                                    disabled={page >= totalPages}
                                    onClick={() => setPage((p) => Math.min(p + 1, totalPages))}
                                    className="px-2 py-1 text-xs"
                                >
                                    Next
                                </Button>
                            </div>
                        </div>
                    </>
                )}
            </div>

            <Dialog open={deleteTarget !== null} onOpenChange={(open) => !open && closeDeleteDialog()}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete referral transaction</DialogTitle>
                        <DialogDescription>
                            This permanently deletes the referral and its clinical, patient, tracking, status, provider, medicine, follow-up, and attachment records.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-2">
                        <p className="text-sm text-slate-700">
                            Enter <strong>{deleteTarget?.LogID}</strong> to confirm.
                        </p>
                        <Input
                            value={deleteConfirmation}
                            onChange={(event) => setDeleteConfirmation(event.target.value)}
                            placeholder={deleteTarget?.LogID}
                            autoComplete="off"
                            disabled={deleting}
                        />
                        {deleteError && (
                            <p className="text-sm text-red-600" role="alert">
                                {deleteError}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={closeDeleteDialog} disabled={deleting}>
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => void deleteReferral()}
                            disabled={deleting || !deleteTarget || deleteConfirmation !== deleteTarget.LogID}
                        >
                            {deleting ? 'Deleting...' : 'Delete transaction'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
};

export default Lists;

function FilterField({ label, children }: { label: string; children: ReactNode }) {
    return (
        <label className="min-w-0 space-y-1 text-xs font-medium text-slate-700">
            <span>{label}</span>
            {children}
        </label>
    );
}

function FilterSelect({ value, onChange, children }: { value: string; onChange: (value: string) => void; children: ReactNode }) {
    return (
        <select
            value={value}
            onChange={(event) => onChange(event.target.value)}
            className="border-input bg-background focus-visible:ring-ring h-8 w-full rounded-md border px-2 text-xs shadow-xs outline-none focus-visible:ring-2"
        >
            {children}
        </select>
    );
}
