import { Head, useForm } from "@inertiajs/react";
import { LoaderCircle, Save, User } from "lucide-react";
import { FormEventHandler, useEffect, useRef } from "react";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type RolesFormProps = {
    onRoleCreated: () => void;
};

// Define form type outside for reusability
type RolesForm = {
    name: string;
    guard_name: string;
};

export default function RolesForm({ onRoleCreated }: RolesFormProps) {
    const { data, setData, post, processing, errors, reset } = useForm<RolesForm>({
        name: "",
        guard_name: "",
    });

    const nameInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(e.target.name, e.target.value);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route("roles.store"), {
            onSuccess: () => {
                reset("name", "guard_name");
                onRoleCreated();
            },
        });
    };

    return (
        <div className="w-full max-w-sm p-6 bg-white rounded-2xl ml-1 mt-1 shadow">
            <Head title="Register" />
            <div className="flex items-center mb-4">
                <User size={18} />
                <h2 className="text-lg font-semibold text-gray-800 ml-2">Roles</h2>
            </div>

            <form className="flex flex-col gap-4" onSubmit={submit}>
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
                        disabled={processing}
                        placeholder="Role"
                        className="focus:ring focus:ring-indigo-300"
                        aria-describedby={errors.name ? "name-error" : undefined}
                    />
                    <InputError id="name-error" message={errors.name} aria-live="polite" className="text-xs text-red-500" />
                </div>

                {/* Guard Field */}
                <div className="grid gap-1">
                    <Label htmlFor="guard_name">Guard:</Label>
                    <Select value={data.guard_name} onValueChange={(value) => setData("guard_name", value)} disabled={processing}>
                        <SelectTrigger className="w-full border-gray-300 rounded-md shadow-sm">
                            <SelectValue placeholder="Select" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="web">Web</SelectItem>
                            <SelectItem value="api">API</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError id="guard_name-error" message={errors.guard_name} aria-live="polite" className="text-xs text-red-500" />
                </div>

                {/* Button Container for Inline Layout */}
                <div className="mt-4 flex justify-between gap-4">

                    {/* Submit Button */}
                    <Button
                        type="submit"
                        className="flex-1 flex justify-center items-center gap-2 border-1 border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        disabled={processing}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        <span>
                            {processing ? 'Processing...' : (
                                <>
                                    <Save size={12} />
                                </>
                            )}
                        </span>
                    </Button>

                </div>
            </form>
        </div>
    );
}
