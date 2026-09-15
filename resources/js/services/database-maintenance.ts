import axios from 'axios';

export type MaintenanceRun = {
    id: string;
    action: string;
    seeder: string | null;
    actor_id: number;
    started_at: string;
    finished_at: string | null;
    status: string;
    output: string;
};
export type MaintenanceStatus = {
    running: boolean;
    migrations: { name: string; ran: boolean }[];
    seeders: { key: string; label: string }[];
    history: MaintenanceRun[];
};

export async function maintenanceStatus() {
    return (await axios.get<MaintenanceStatus>('/admin/database-maintenance/status')).data;
}

export async function runMaintenance(action: 'migrate' | 'seed', seeder: string) {
    return (await axios.post<{ result: MaintenanceRun }>('/admin/database-maintenance', { action, seeder, confirmation: true })).data.result;
}
