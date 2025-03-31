import React from "react";
import { Head, useForm } from "@inertiajs/react";

export default function Create({ permissions }) {
    const { data, setData, post } = useForm({
        name: "",
        permissions: [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("roles.store"));
    };

    return (
        <div className="container mt-4">
            <Head title="Create Role" />

            <h1 className="text-2xl font-bold mb-4">Create Role</h1>

            <form onSubmit={handleSubmit}>
                <div className="mb-3">
                    <label className="form-label">Role Name</label>
                    <input type="text" className="form-control" value={data.name} onChange={(e) => setData("name", e.target.value)} required />
                </div>

                <div className="mb-3">
                    <label className="form-label">Permissions</label>
                    {permissions.map((permission) => (
                        <div key={permission.id}>
                            <input type="checkbox" value={permission.name} onChange={(e) => {
                                const newPermissions = e.target.checked
                                    ? [...data.permissions, e.target.value]
                                    : data.permissions.filter((p) => p !== e.target.value);
                                setData("permissions", newPermissions);
                            }} />
                            {permission.name}
                        </div>
                    ))}
                </div>

                <button type="submit" className="btn btn-success">Create Role</button>
            </form>
        </div>
    );
}
