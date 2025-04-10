import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Save, User,X } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeadingSmall from '@/components/heading-small';
import { RegisterForm } from './types';

type UserFormProps = {
  onUserCreated: () => void;
  onCancel: () => void;  // onCancel prop to handle the cancel action
  user?: any;  // User object for editing (optional)
};

export default function UsersForm({ onUserCreated, onCancel, user }: UserFormProps) {
  const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
    name: user?.name || '',
    email: user?.email || '',
    password: user?.password || '',
    password_confirmation: user?.password_confirmation || '',
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
    post(route('user.store'), {
      onSuccess: () => {
        reset();
        onUserCreated();
      },
    });
  };

  return (
    <div className="w-full ml-2 mt-2 mr-3">
      <Head title="Register" />
      <div className="flex items-center mb-2">
        <User size={18} />
        <h1 className="text-lg font-semibold text-gray-800 ml-2">{user ? 'Edit User' : 'Create User'}</h1>
      </div>
      <HeadingSmall title="Profile information" description="Enter your details below to create your account" />
      <div className="mb-4"></div>
      <form className="flex flex-col gap-4 mt-4" onSubmit={submit}>
        <div className="grid gap-4">
          {/* Name Field */}
          <div className="grid gap-1">
            <Label htmlFor="name">Name</Label>
            <Input
              ref={nameInputRef}
              id="name"
              type="text"
              autoComplete="off"
              value={data.name}
              onChange={handleChange}
              disabled={processing}
              placeholder="Full name"
              className="focus:ring focus:ring-indigo-300 text-sm py-1 px-2"
              aria-describedby={errors.name ? 'name-error' : undefined}
            />
            <InputError id="name-error" message={errors.name} aria-live="polite" className="text-xs text-red-500"/>
          </div>

          {/* Email Field */}
          <div className="grid gap-1">
            <Label htmlFor="email">Email Address</Label>
            <Input
              id="email"
              type="email"
              autoComplete="off"
              value={data.email}
              onChange={handleChange}
              disabled={processing}
              placeholder="email@example.com"
              className="focus:ring focus:ring-indigo-300"
              aria-describedby={errors.email ? 'email-error' : undefined}
            />
            <InputError id="email-error" message={errors.email} aria-live="polite" className="text-xs text-red-500"/>
          </div>

          {/* Password Field */}
          <div className="grid gap-1">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              type="password"
              autoComplete="off"
              value={data.password}
              onChange={handleChange}
              disabled={processing}
              placeholder="Password"
              className="focus:ring focus:ring-indigo-300"
              aria-describedby={errors.password ? 'password-error' : undefined}
            />
            <InputError id="password-error" message={errors.password} aria-live="polite" className="text-xs text-red-500" />
          </div>

          {/* Confirm Password Field */}
          <div className="grid gap-1">
            <Label htmlFor="password_confirmation">Confirm Password</Label>
            <Input
              id="password_confirmation"
              type="password"
              autoComplete="off"
              value={data.password_confirmation}
              onChange={handleChange}
              disabled={processing}
              placeholder="Confirm password"
              className="focus:ring focus:ring-indigo-300"
              aria-describedby={errors.password_confirmation ? 'password_confirmation-error' : undefined}
            />
            <InputError id="password_confirmation-error" message={errors.password_confirmation} aria-live="polite"  className="text-xs text-red-500"/>
          </div>

          {/* Submit and Cancel Buttons */}
          <div className="mt-4 flex justify-between gap-4">
                    <Button
                        type="submit"
                        className="flex-1 flex justify-center items-center gap-2 border-1 border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        disabled={processing}
                    >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        <span>{processing ? 'Processing...' : <><Save size={12} /></>}</span>
                    </Button>

                    {/* Cancel Button (only shown when editing a role) */}
                    {user && (
                        <Button
                            type="button"
                            onClick={onCancel} // Trigger the onCancel function passed from parent
                            className="flex-1 flex justify-center items-center gap-2 border-1 border-red-400 bg-white text-red-600 hover:bg-red-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                        >
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        <span>{processing ? 'Processing...' : <><X  size={12} /></>}</span>
                        </Button>
                    )}
          </div>
        </div>
      </form>
    </div>
  );
}
