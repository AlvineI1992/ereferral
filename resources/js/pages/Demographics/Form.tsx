import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import axios from 'axios';
import { Building2, Landmark, LoaderCircle, MapPinned, Route, Save, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { toast } from 'sonner';
import { DEMOGRAPHIC_LEVELS, type DemographicLevel, type DemographicOption, type DemographicRecord, type DemographicScope } from './types';

type Props = {
    level: DemographicLevel;
    formval?: DemographicRecord | null;
    scope: DemographicScope;
    onCreated: () => void;
    onCancel?: () => void;
};

type FormState = {
    code: string;
    name: string;
    status: boolean;
    regionCode: string;
    provinceCode: string;
    cityCode: string;
    regAbbrev: string;
    nscbCode: string;
    nscbName: string;
    newCode: string;
    cityClassification: string;
    chartered: '' | 'Y' | 'N';
};

const icons = {
    region: Landmark,
    province: Building2,
    city: Route,
    barangay: MapPinned,
};

export default function DemographicForm({ level, formval, scope, onCreated, onCancel }: Props) {
    const isEditMode = Boolean(formval?.code);
    const activeLevel = useMemo(() => DEMOGRAPHIC_LEVELS.find((item) => item.value === level) ?? DEMOGRAPHIC_LEVELS[0], [level]);
    const Icon = icons[level];
    const codeInputRef = useRef<HTMLInputElement>(null);

    const [data, setData] = useState<FormState>({
        code: '',
        name: '',
        status: true,
        regionCode: '',
        provinceCode: '',
        cityCode: '',
        regAbbrev: '',
        nscbCode: '',
        nscbName: '',
        newCode: '',
        cityClassification: '',
        chartered: '',
    });
    const [regionOptions, setRegionOptions] = useState<DemographicOption[]>([]);
    const [provinceOptions, setProvinceOptions] = useState<DemographicOption[]>([]);
    const [cityOptions, setCityOptions] = useState<DemographicOption[]>([]);
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        setData({
            code: formval?.code ?? '',
            name: formval?.name ?? '',
            status: formval ? formval.status === 'A' : true,
            regionCode: formval?.parent_region_code ?? scope.regionCode ?? '',
            provinceCode: formval?.parent_province_code ?? scope.provinceCode ?? '',
            cityCode: formval?.parent_city_code ?? scope.cityCode ?? '',
            regAbbrev: formval?.regabbrev ?? '',
            nscbCode: formval?.nscb_code ?? '',
            nscbName: formval?.nscb_name ?? '',
            newCode: formval?.newcode ?? '',
            cityClassification: formval?.cityclassification ? String(formval.cityclassification) : '',
            chartered: (formval?.chartered as '' | 'Y' | 'N' | null) ?? '',
        });
        setFieldErrors({});
    }, [formval, level, scope.cityCode, scope.provinceCode, scope.regionCode]);

    useEffect(() => {
        codeInputRef.current?.focus();
    }, [level, isEditMode]);

    useEffect(() => {
        const loadRegions = async () => {
            try {
                const response = await axios.get('/demographics/options/region');
                setRegionOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load region options:', error);
            }
        };

        void loadRegions();
    }, []);

    useEffect(() => {
        if (level === 'region' || data.regionCode === '') {
            setProvinceOptions([]);
            return;
        }

        const loadProvinces = async () => {
            try {
                const response = await axios.get('/demographics/options/province', {
                    params: {
                        region_code: data.regionCode,
                    },
                });
                setProvinceOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load province options:', error);
            }
        };

        void loadProvinces();
    }, [data.regionCode, level]);

    useEffect(() => {
        if (level !== 'barangay' || data.provinceCode === '') {
            setCityOptions([]);
            return;
        }

        const loadCities = async () => {
            try {
                const response = await axios.get('/demographics/options/city', {
                    params: {
                        province_code: data.provinceCode,
                    },
                });
                setCityOptions(response.data.data ?? []);
            } catch (error) {
                console.error('Unable to load city options:', error);
            }
        };

        void loadCities();
    }, [data.provinceCode, level]);

    const setField = <K extends keyof FormState>(field: K, value: FormState[K]) => {
        setData((current) => ({ ...current, [field]: value }));
        setFieldErrors((current) => ({ ...current, [field]: '' }));
    };

    const handleRegionChange = (value: string) => {
        setData((current) => ({
            ...current,
            regionCode: value,
            provinceCode: '',
            cityCode: '',
        }));
        setFieldErrors((current) => ({ ...current, regionCode: '', provinceCode: '', cityCode: '' }));
    };

    const handleProvinceChange = (value: string) => {
        const province = provinceOptions.find((item) => item.code === value);

        setData((current) => ({
            ...current,
            provinceCode: value,
            regionCode: province?.regcode ?? current.regionCode,
            cityCode: '',
        }));
        setFieldErrors((current) => ({ ...current, provinceCode: '', cityCode: '' }));
    };

    const handleCityChange = (value: string) => {
        const city = cityOptions.find((item) => item.code === value);

        setData((current) => ({
            ...current,
            cityCode: value,
            provinceCode: city?.provcode ?? current.provinceCode,
            regionCode: city?.regcode ?? current.regionCode,
        }));
        setFieldErrors((current) => ({ ...current, cityCode: '' }));
    };

    const buildPayload = () => {
        const status = data.status ? 'A' : 'I';

        if (level === 'region') {
            return {
                regcode: data.code.trim(),
                regname: data.name.trim(),
                regabbrev: data.regAbbrev.trim() || null,
                nscb_reg_code: data.nscbCode.trim() || null,
                nscb_reg_name: data.nscbName.trim() || null,
                status,
            };
        }

        if (level === 'province') {
            return {
                provcode: data.code.trim(),
                regcode: data.regionCode,
                provname: data.name.trim(),
                nscb_prov_code: data.nscbCode.trim() || null,
                nscb_prov_name: data.nscbName.trim() || null,
                newcode: data.newCode.trim() || null,
                status,
            };
        }

        if (level === 'city') {
            return {
                citycode: data.code.trim(),
                regcode: data.regionCode || null,
                provcode: data.provinceCode,
                cityname: data.name.trim(),
                nscb_city_code: data.nscbCode.trim() || null,
                nscb_city_name: data.nscbName.trim() || null,
                cityclassification: data.cityClassification ? Number(data.cityClassification) : null,
                chartered: data.chartered || null,
                newcode: data.newCode.trim() || null,
                status,
            };
        }

        return {
            bgycode: data.code.trim(),
            regcode: data.regionCode || null,
            provcode: data.provinceCode || null,
            citycode: data.cityCode,
            bgyname: data.name.trim(),
            nscb_brgy_code: data.nscbCode.trim() || null,
            nscb_brgy_name: data.nscbName.trim() || null,
            newcode: data.newCode.trim() || null,
            status,
        };
    };

    const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        setSaving(true);
        setFieldErrors({});

        try {
            const payload = buildPayload();

            if (isEditMode) {
                await axios.put(`/demographics/update/${level}/${formval!.code}`, payload);
                toast.success(`${activeLevel.shortTitle} updated.`);
            } else {
                await axios.post(`/demographics/store/${level}`, payload);
                toast.success(`${activeLevel.shortTitle} created.`);
            }

            onCreated();

            if (!isEditMode) {
                setData((current) => ({
                    ...current,
                    code: '',
                    name: '',
                    regAbbrev: '',
                    nscbCode: '',
                    nscbName: '',
                    newCode: '',
                    cityClassification: '',
                    chartered: '',
                    status: true,
                }));
            }
        } catch (error: unknown) {
            if (axios.isAxiosError(error) && error.response?.status === 422) {
                const validationErrors = error.response.data.errors ?? {};
                const normalizedErrors = Object.fromEntries(
                    Object.entries(validationErrors).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value]),
                ) as Record<string, string>;
                setFieldErrors(normalizedErrors);
                toast.error(`Please review the ${activeLevel.shortTitle.toLowerCase()} form.`);
            } else {
                console.error('Unable to save demographic record:', error);
                toast.error(`Unable to save ${activeLevel.shortTitle.toLowerCase()} right now.`);
            }
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="w-full">
            <div className="rounded-2xl border bg-white p-5 shadow-sm">
                <div className="mb-4 flex items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <div className="rounded-2xl bg-emerald-50 p-3 text-emerald-700">
                            <Icon className="size-5" />
                        </div>
                        <div>
                            <h2 className="text-lg font-semibold">{isEditMode ? `Edit ${activeLevel.shortTitle}` : `Create ${activeLevel.shortTitle}`}</h2>
                            <p className="text-muted-foreground text-xs">{activeLevel.levelLabel}</p>
                        </div>
                    </div>
                    <Badge variant={data.status ? 'default' : 'outline'}>{data.status ? 'Active' : 'Inactive'}</Badge>
                </div>

                <HeadingSmall title={`${activeLevel.shortTitle} Reference`} description={activeLevel.description} />

                <form className="mt-5 space-y-4" onSubmit={handleSubmit}>
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                ref={codeInputRef}
                                value={data.code}
                                onChange={(event) => setField('code', event.target.value.toUpperCase().trim())}
                                placeholder={level === 'region' ? '01' : level === 'province' ? '0128' : level === 'city' ? '012801' : '012801001'}
                                disabled={isEditMode}
                                autoComplete="off"
                            />
                            <InputError message={fieldErrors.code || fieldErrors.regcode || fieldErrors.provcode || fieldErrors.citycode || fieldErrors.bgycode} />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input id="name" value={data.name} onChange={(event) => setField('name', event.target.value)} autoComplete="off" />
                            <InputError
                                message={fieldErrors.name || fieldErrors.regname || fieldErrors.provname || fieldErrors.cityname || fieldErrors.bgyname}
                            />
                        </div>
                    </div>

                    {level !== 'region' && (
                        <div className={`grid gap-4 ${level === 'barangay' ? 'md:grid-cols-3' : 'md:grid-cols-2'}`}>
                            <div className="space-y-2">
                                <Label htmlFor="regionCode">Region</Label>
                                <select
                                    id="regionCode"
                                    value={data.regionCode}
                                    onChange={(event) => handleRegionChange(event.target.value)}
                                    className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="">Select region</option>
                                    {regionOptions.map((option) => (
                                        <option key={option.code} value={option.code}>
                                            {option.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={fieldErrors.regionCode || fieldErrors.regcode} />
                            </div>

                            {(level === 'city' || level === 'barangay') && (
                                <div className="space-y-2">
                                    <Label htmlFor="provinceCode">Province</Label>
                                    <select
                                        id="provinceCode"
                                        value={data.provinceCode}
                                        onChange={(event) => handleProvinceChange(event.target.value)}
                                        disabled={!data.regionCode}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <option value="">Select province</option>
                                        {provinceOptions.map((option) => (
                                            <option key={option.code} value={option.code}>
                                                {option.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.provinceCode || fieldErrors.provcode} />
                                </div>
                            )}

                            {level === 'barangay' && (
                                <div className="space-y-2">
                                    <Label htmlFor="cityCode">City / Municipality</Label>
                                    <select
                                        id="cityCode"
                                        value={data.cityCode}
                                        onChange={(event) => handleCityChange(event.target.value)}
                                        disabled={!data.provinceCode}
                                        className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <option value="">Select city / municipality</option>
                                        {cityOptions.map((option) => (
                                            <option key={option.code} value={option.code}>
                                                {option.name}
                                            </option>
                                        ))}
                                    </select>
                                    <InputError message={fieldErrors.cityCode || fieldErrors.citycode} />
                                </div>
                            )}
                        </div>
                    )}

                    <div className="grid gap-4 md:grid-cols-2">
                        {level === 'region' && (
                            <div className="space-y-2">
                                <Label htmlFor="regAbbrev">Abbreviation</Label>
                                <Input id="regAbbrev" value={data.regAbbrev} onChange={(event) => setField('regAbbrev', event.target.value.toUpperCase())} />
                                <InputError message={fieldErrors.regabbrev} />
                            </div>
                        )}

                        {(level === 'province' || level === 'city' || level === 'barangay') && (
                            <div className="space-y-2">
                                <Label htmlFor="newCode">New code</Label>
                                <Input id="newCode" value={data.newCode} onChange={(event) => setField('newCode', event.target.value.toUpperCase())} />
                                <InputError message={fieldErrors.newcode} />
                            </div>
                        )}

                        <div className="space-y-2">
                            <Label htmlFor="nscbCode">NSCB code</Label>
                            <Input id="nscbCode" value={data.nscbCode} onChange={(event) => setField('nscbCode', event.target.value.toUpperCase())} />
                            <InputError
                                message={fieldErrors.nscb_reg_code || fieldErrors.nscb_prov_code || fieldErrors.nscb_city_code || fieldErrors.nscb_brgy_code}
                            />
                        </div>

                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="nscbName">NSCB name</Label>
                            <Input id="nscbName" value={data.nscbName} onChange={(event) => setField('nscbName', event.target.value)} />
                            <InputError
                                message={fieldErrors.nscb_reg_name || fieldErrors.nscb_prov_name || fieldErrors.nscb_city_name || fieldErrors.nscb_brgy_name}
                            />
                        </div>
                    </div>

                    {level === 'city' && (
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="cityClassification">City classification</Label>
                                <Input
                                    id="cityClassification"
                                    type="number"
                                    min="1"
                                    max="9"
                                    value={data.cityClassification}
                                    onChange={(event) => setField('cityClassification', event.target.value)}
                                />
                                <InputError message={fieldErrors.cityclassification} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="chartered">Chartered</Label>
                                <select
                                    id="chartered"
                                    value={data.chartered}
                                    onChange={(event) => setField('chartered', event.target.value as '' | 'Y' | 'N')}
                                    className="border-input bg-background ring-offset-background h-10 w-full rounded-md border px-3 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                >
                                    <option value="">Select flag</option>
                                    <option value="Y">Yes</option>
                                    <option value="N">No</option>
                                </select>
                                <InputError message={fieldErrors.chartered} />
                            </div>
                        </div>
                    )}

                    <div className="flex items-center justify-between rounded-2xl border px-4 py-3">
                        <div>
                            <p className="text-sm font-medium">Active status</p>
                            <p className="text-muted-foreground text-xs">Inactive records stay in history but can be filtered out from maintenance views.</p>
                        </div>
                        <Switch checked={data.status} onCheckedChange={(checked) => setField('status', checked)} />
                    </div>
                    <InputError message={fieldErrors.status || fieldErrors.form} />

                    <div className="flex gap-3">
                        <Button type="submit" className="flex-1" disabled={saving}>
                            {saving ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                            {isEditMode ? 'Save changes' : `Create ${activeLevel.shortTitle}`}
                        </Button>

                        {isEditMode && (
                            <Button type="button" variant="outline" className="flex-1" onClick={onCancel} disabled={saving}>
                                <X className="size-4" />
                                Cancel
                            </Button>
                        )}
                    </div>
                </form>
            </div>
        </div>
    );
}
