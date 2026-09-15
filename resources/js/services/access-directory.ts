import axios from 'axios';

export type DirectoryKind = 'roles' | 'permission';
export type AccessRecord = {
    id: number;
    name: string;
    guard_name: string;
    classification: string[];
    action: string | null;
    assignment_count: number;
    endpoint?: string | null;
};
export type DirectoryFilters = {
    search: string;
    module: string;
    action: string;
    guard: string;
    assignment: string;
    sort: string;
    per_page: number;
    page: number;
};
export type DirectoryResponse = {
    data: AccessRecord[];
    total: number;
    last_page: number;
    filter_options: { modules: string[]; actions: string[]; guards: string[] };
};

export async function loadAccessDirectory(kind: DirectoryKind, filters: DirectoryFilters, signal: AbortSignal) {
    return (await axios.get<DirectoryResponse>(`/${kind}/list`, { params: filters, signal })).data;
}

export async function deleteAccessRecord(kind: DirectoryKind, id: number) {
    await axios.delete(`/${kind}/delete/${id}`);
}
