export type DemographicLevel = 'region' | 'province' | 'city' | 'barangay';

export type DemographicRecord = {
    level: DemographicLevel;
    code: string;
    name: string;
    status: 'A' | 'I' | string;
    status_label?: string;
    updated_at?: string | null;
    addedby?: string | null;
    regabbrev?: string | null;
    nscb_code?: string | null;
    nscb_name?: string | null;
    newcode?: string | null;
    cityclassification?: number | string | null;
    chartered?: 'Y' | 'N' | '' | null;
    parent_region_code?: string | null;
    parent_region_name?: string | null;
    parent_province_code?: string | null;
    parent_province_name?: string | null;
    parent_city_code?: string | null;
    parent_city_name?: string | null;
};

export type DemographicOption = {
    code: string;
    name: string;
    status?: string;
    regcode?: string;
    provcode?: string;
    citycode?: string;
};

export type DemographicScope = {
    regionCode: string;
    provinceCode: string;
    cityCode: string;
};

export const DEMOGRAPHIC_LEVELS: Array<{
    value: DemographicLevel;
    title: string;
    shortTitle: string;
    levelLabel: string;
    description: string;
}> = [
    {
        value: 'region',
        title: 'Regions',
        shortTitle: 'Region',
        levelLabel: 'Level 1',
        description: 'Top-level administrative group used to scope provinces.',
    },
    {
        value: 'province',
        title: 'Provinces',
        shortTitle: 'Province',
        levelLabel: 'Level 2',
        description: 'Province records anchored to a selected region.',
    },
    {
        value: 'city',
        title: 'Cities / Municipalities',
        shortTitle: 'City',
        levelLabel: 'Level 3',
        description: 'City and municipality references anchored to a province.',
    },
    {
        value: 'barangay',
        title: 'Barangays',
        shortTitle: 'Barangay',
        levelLabel: 'Level 4',
        description: 'Barangay records filtered by city to keep the dataset manageable.',
    },
];
