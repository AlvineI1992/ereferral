import { Head, useForm } from "@inertiajs/react";
import { LoaderCircle, Save, User, X,Edit} from "lucide-react";
import { FormEventHandler, useEffect, useRef } from "react";
import InputError from "@/components/input-error";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import HeadingSmall from '@/components/heading-small';
import toastr from 'toastr';
import {show} from './Show';


import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";

type PermissionFormProps = {
    onCreated: () => void;
    onCancel: () => void;  
    perm?: { id: number; name: string; guard_name: string }; 
    canCreate:boolean;
};

type PermissionForm = {
    name: string;
    guard_name: string;
};

export default function PermissionForm({ canCreate,onCreated, onCancel, perm }: PermissionFormProps) {
    const { data, setData, post, processing, errors, reset, put } = useForm<PermissionForm>({
        name: perm?.name || "", 
        guard_name: perm?.guard_name || "", 
    });

    const nameInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    useEffect(() => {
        if (perm) {
            setData({
                name: perm.name,
                guard_name: perm.guard_name,
            });
        } else {
            reset();
        }
    }, [perm]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(e.target.name, e.target.value);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (perm) {
           
            put(route("permission.update", perm.id), {
                onSuccess: () => {
                    reset();
                    onCreated();
                    toastr.success('Data Saved!', 'Success');
                },
            });
        } else {
           
            post(route("permission.store"), {
                onSuccess: () => {
                    reset();
                    onCreated();
                    toastr.success('Data Saved!', 'Success');
                },
            });
        }
    };

 

    return (
        <div className="w-full ml-2 mt-2 mr-3 ">
            <Head title="Permission Management" />
            <div className="flex items-center mb-2">
                
                {perm ? <Edit size={18}/>: <User size={18} />}
                <h1 className="text-lg font-semibold ml-2">{perm ? 'Edit Permission' : 'Create Permission'} </h1>
            </div>
            <HeadingSmall title={perm ? "Edit Permission" : "Create Permission"} description={perm ? "Edit the permission details below." : "Enter the permission details below."} />

            <form className="flex flex-col gap-1 mt-4" onSubmit={submit}>
                {/* Name Field */}
                <div className="grid gap-1">
                    <Label htmlFor="name">Permission Name:</Label>
                    <Input
                        ref={nameInputRef}
                        id="name"
                        name="name"
                        type="text"
                        autoComplete="off"
                        value={data.name}
                        onChange={handleChange}
                        disabled={processing||!canCreate }
                        placeholder="Enter Permission Name"
                        className="focus:ring focus:ring-indigo-300"
                        aria-describedby={errors.name ? "name-error" : undefined}
                    />
                    <InputError id="name-error" message={errors.name} aria-live="polite" className="text-xs text-red-500" />
                </div>

                {/* Guard Field */}
                <div className="grid gap-1">
                    <Label htmlFor="guard_name">Guard:</Label>
                    <Select
                    
                        value={data.guard_name}
                        onValueChange={(value) => setData("guard_name", value)}
                        disabled={processing||!canCreate }
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
                    <InputError id="guard_name-error" message={errors.guard_name} aria-live="polite" className="text-xs text-red-500" />
                </div>

                {/* Submit and Cancel Buttons */}
                <div className="mt-4 flex justify-between gap-4">
                    <Button
                        type="submit"
                        className="flex-1 flex justify-center items-center gap-2 border-1 border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        disabled={processing||!canCreate}
                    >
                        {processing ? (
                            <>
                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                <span>Processing...</span>
                            </>
                        ) : (
                            <>
                                <Save size={12} />
                             
                            </>
                        )}
                    </Button>

                    {/* Cancel Button (only shown when editing a role) */}
                    {perm && (
                        <Button
                            type="button"
                            onClick={onCancel} 
                            className="flex-1 flex justify-center items-center gap-2 border-1 border-red-400 bg-white text-red-600 hover:bg-red-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        >
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            <span>{processing ? 'Processing...' : <><X size={12} /></>}</span>
                        </Button>
                    )}
                </div>
            </form>
        </div>
    );
}
