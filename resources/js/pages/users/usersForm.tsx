import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle,ArrowLeft,Save,User } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeadingSmall from '@/components/heading-small';

// Define form type outside for reusability
type RegisterForm = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function userForm() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
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
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout title="Create an Account" description="Enter your details below to create your account">
            <div className="min-h-screen flex items-start  justify-start">
                <div className="w-full max-w-lg p-8 bg-white  rounded-2xl ml-4 mt-3 ">
                    <Head title="Register" />
                    <div className="flex items-center gap-2 mb-6">
                        <User size={16} />
                        <h2 className="text-xl font-bold text-gray-800 text-left">Create an Account</h2>
                    </div>

                    <HeadingSmall title="Profile information" description="Enter your details below to create your account" />
                    <div className="mb-6"></div>
                    <form className="flex flex-col gap-6" onSubmit={submit}>
                        <div className="grid gap-4">
                            {/* Name Field */}
                            <div className="grid gap-1">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    ref={nameInputRef}
                                    id="name"
                                    type="text"
                                    required
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={handleChange}
                                    disabled={processing}
                                    placeholder="Full name"
                                    className="focus:ring focus:ring-indigo-300"
                                    aria-describedby={errors.name ? 'name-error' : undefined}
                                />
                                <InputError id="name-error" message={errors.name} aria-live="polite" />
                            </div>

                            {/* Email Field */}
                            <div className="grid gap-1">
                                <Label htmlFor="email">Email Address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    autoComplete="email"
                                    value={data.email}
                                    onChange={handleChange}
                                    disabled={processing}
                                    placeholder="email@example.com"
                                    className="focus:ring focus:ring-indigo-300"
                                    aria-describedby={errors.email ? 'email-error' : undefined}
                                />
                                <InputError id="email-error" message={errors.email} aria-live="polite" />
                            </div>

                            {/* Password Field */}
                            <div className="grid gap-1">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    required
                                    autoComplete="new-password"
                                    value={data.password}
                                    onChange={handleChange}
                                    disabled={processing}
                                    placeholder="Password"
                                    className="focus:ring focus:ring-indigo-300"
                                    aria-describedby={errors.password ? 'password-error' : undefined}
                                />
                                <InputError id="password-error" message={errors.password} aria-live="polite" />
                            </div>

                            {/* Confirm Password Field */}
                            <div className="grid gap-1">
                                <Label htmlFor="password_confirmation">Confirm Password</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    required
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={handleChange}
                                    disabled={processing}
                                    placeholder="Confirm password"
                                    className="focus:ring focus:ring-indigo-300"
                                    aria-describedby={errors.password_confirmation ? 'password_confirmation-error' : undefined}
                                />
                                <InputError id="password_confirmation-error" message={errors.password_confirmation} aria-live="polite" />
                            </div>
                        {/* Button Container for Inline Layout */}
                        <div className="mt-4 flex justify-between gap-4">
                            {/* Back Button */}
                            <Button
                                type="button"
                                className="flex-1 flex justify-center items-center gap-2 border-1 border-red-500 bg-white text-red-500 hover:bg-red-500 hover:text-white font-semibold py-2 rounded-md transition-all"
                                onClick={() => window.history.back()}
                            >
                                <ArrowLeft size={16} /> Back
                            </Button>

                            {/* Submit Button */}
                            <Button
                                type="submit"
                                className="flex-1 flex justify-center items-center gap-2 border-1 border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                                disabled={processing}
                            >
                                <Save size={16} />
                                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                <span>{processing ? 'Processing...' : 'Save'}</span>
                            </Button>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
