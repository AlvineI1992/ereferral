import React from "react";
import { Head, Link, useForm } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Table, TableHeader, TableRow, TableHead, TableBody, TableCell } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent } from "@/components/ui/card";

export default function Index({ roles }) {
    const { delete: destroy } = useForm();

    const deleteRole = (id) => {
        if (confirm("Are you sure you want to delete this role?")) {
            destroy(route("roles.destroy", id));
        }
    };

    return (
        <div className="container mt-4">
            <Head title="Roles" />

            <h1 className="text-2xl font-bold mb-4">Roles</h1>

            <Link href="/roles/create">
                <Button className="mb-3">Create Role</Button>
            </Link>

            <Card>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Name</TableHead>
                                <TableHead>Permissions</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell>{role.id}</TableCell>
                                    <TableCell>{role.name}</TableCell>
                                    <TableCell>
                                        {role.permissions.map((perm) => (
                                            <Badge key={perm.id} className="me-1">{perm.name}</Badge>
                                        ))}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={route("roles.edit", role.id)}>
                                            <Button variant="outline" className="me-1">Edit</Button>
                                        </Link>
                                        <Button variant="destructive" onClick={() => deleteRole(role.id)}>Delete</Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    );
}
