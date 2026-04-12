export type ReligionRecord = {
    relcode: string;
    reldesc: string;
    relstat: 'A' | 'I';
    updated_at?: string | null;
};
