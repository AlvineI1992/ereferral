import { Head, useForm } from "@inertiajs/react";
import { LoaderCircle, Save, User, X } from "lucide-react";
import { FormEventHandler, useEffect, useRef } from "react";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import HeadingSmall from "@/components/heading-small";
import toastr from "toastr";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type RolesFormProps = {
    onRoleCreated: () => void;
    onCancel: () => void;
    role?: { id: number; name: string; guard_name: string };
    canCreate: boolean;
};

type RolesForm = {
    name: string;
    guard_name: string;
};

export default function RolesForm({ canCreate, onRoleCreated, onCancel, role }: RolesFormProps) {
    const { data, setData, post, processing, errors, reset, put } = useForm<RolesForm>({
        name: role?.name || "",
        guard_name: role?.guard_name || "",
    });

    const nameInputRef = useRef<HTMLInputElement>(null);

    // Focus on input on load
    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    // Dynamically update form when role changes
    useEffect(() => {
        if (role) {
            setData({
                name: role.name,
                guard_name: role.guard_name,
            });
        } else {
            reset();
        }
    }, [role]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(e.target.name, e.target.value);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (role) {
            put(route("roles.update", role.id), {
                onSuccess: () => {
                    reset();
                    onRoleCreated();
                    toastr.success("Role updated!", "Success");
                },
            });
        } else {
            post(route("roles.store"), {
                onSuccess: () => {
                    reset();
                    onRoleCreated();
                    toastr.success("Role created!", "Success");
                },
            });
        }
    };

    const handleCancel = () => {
        reset();
        onCancel();
    };

    return (
        <div className="w-full ml-2 mt-2 mr-3">
            <Head title="Role Management" />
            <div className="flex items-center mb-2">
                <User size={18} />
                <h1 className="text-lg font-semibold ml-2">{role ? "Edit Role" : "Create Role"}</h1>
            </div>
            <HeadingSmall
                title={role ? "Edit Role" : "Create Role"}
                description={role ? "Edit the role details below." : "Enter the role details below."}
            />

            <form className="flex flex-col gap-1 mt-4" onSubmit={submit}>
                {/* Name Field */}
                <div className="grid gap-1">
                    <Label htmlFor="name">Role Name:</Label>
                    <Input
                        ref={nameInputRef}
                        id="name"
                        name="name"
                        type="text"
                        autoComplete="off"
                        value={data.name}
                        onChange={handleChange}
                        disabled={processing || !canCreate}
                        placeholder="Enter Role Name"
                        className="focus:ring focus:ring-indigo-300"
                        aria-describedby={errors.name ? "name-error" : undefined}
                    />
                    <InputError
                        id="name-error"
                        message={errors.name}
                        aria-live="polite"
                        className="text-xs text-red-500"
                    />
                </div>

                {/* Guard Field */}
                <div className="grid gap-1">
                    <Label htmlFor="guard_name">Guard:</Label>
                    <Select
                        value={data.guard_name}
                        onValueChange={(value) => setData("guard_name", value)}
                        disabled={processing || !canCreate}
                        aria-labelledby="guard_name"
                    >
                        <SelectTrigger className="w-full border-gray-300 rounded-md shadow-sm">
                            <SelectValue placeholder="Select Guard" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="web">Web</SelectItem>
                            <SelectItem value="api">API</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError
                        id="guard_name-error"
                        message={errors.guard_name}
                        aria-live="polite"
                        className="text-xs text-red-500"
                    />
                </div>

                {/* Submit and Cancel Buttons */}
                <div className="mt-4 flex justify-between gap-4">
                    <Button
                        type="submit"
                        className="flex-1 flex justify-center items-center gap-2 border border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        disabled={processing || !canCreate}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        <span>{processing ? "Processing..." : <><Save size={12} /></>}</span>
                    </Button>

                    {role && (
                        <Button
                            disabled={processing || !canCreate}
                            type="button"
                            onClick={handleCancel}
                            className="flex-1 flex justify-center items-center gap-2 border border-red-400 bg-white text-red-600 hover:bg-red-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            <span>{processing ? "Processing..." : <><X size={12} /></>}</span>
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
