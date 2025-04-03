import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, ArrowLeft, Save, User } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeadingSmall from '@/components/heading-small';

// Define form type outside for reusability
type RolesForm = {
    name: string;
    guard_name: string;
};

export default function rolesForm() {
    const { data, setData, post, processing, errors, reset } = useForm<RolesForm>({
        name: '',
        guard_name: '',
    });

    const nameInputRef = useRef<HTMLInputElement>(null);

    useEffect(() => {
        nameInputRef.current?.focus();
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        setData(e.target.id, e.target.value);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('roles.store'), { // Fixed route
           /*  onFinish: () => reset('guard_name', 'guard'), */
        });
    };


    return (
        <div className="w-full max-w-lg p-8 bg-white  rounded-2xl ml-1 mt-2 ">
            <Head title="Register" />
            <div className="flex items-center gap-1 mb-6">
                <User size={16} />
                <h2 className="text-xl font-bold text-gray-800 text-left">Roles</h2>
            </div>
            <div className="mb-6"></div>
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-4">
                    {/* Name Field */}
                    <div className="grid gap-1">
                        <Label htmlFor="guard_name">Role name:</Label>
                        <Input
                            ref={nameInputRef}
                            id="guard_name"
                            type="text"
                            required
                            autoComplete="off"
                            value={data.guard_name}
                            onChange={handleChange}
                            disabled={processing}
                            placeholder="Guard"
                            className="focus:ring focus:ring-indigo-300"
                            aria-describedby={errors.guard_name ? 'name-error' : undefined}
                        />
                        <InputError id="name-error" message={errors.guard_name} aria-live="polite" />
                    </div>

                    {/* Email Field */}
                    <div className="grid gap-1">
                        <Label htmlFor="guard">Guard:</Label>
                        <select
                            id="guard"
                            name="guard"
                            required
                            value={data.guard}
                            onChange={handleChange}
                            disabled={processing}
                            className="focus:ring focus:ring-indigo-300 border-gray-300 rounded-md shadow-sm"
                        >
                            <option value="">Select</option>
                            <option value="web">Web</option>
                            <option value="api">API</option>
                        </select>
                        <InputError id="guard-error" message={errors.guard} aria-live="polite" />
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
                </div>
            </form>
        </div>
    );
}
