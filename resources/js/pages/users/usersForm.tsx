  import { Head, useForm } from '@inertiajs/react';
  import { LoaderCircle, Save, User, X, Check, ChevronsUpDown,Edit } from 'lucide-react';
  import { FormEventHandler, useEffect, useRef } from 'react';
  import InputError from '@/components/input-error';
  import { Button } from '@/components/ui/button';
  import { Input } from '@/components/ui/input';
  import { Label } from '@/components/ui/label';
  import HeadingSmall from '@/components/heading-small';
  import { RegisterForm } from './types';
  import * as React from "react";
  import {
    Popover,
    PopoverContent,
    PopoverTrigger,
  } from '@/components/ui/popover';
  import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
  } from "@/components/ui/command";
  import { cn } from '@/lib/utils';
  import { useState } from 'react';
  import axios from 'axios';


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
      emr_id: user?.emr_id || '',
    });


    const nameInputRef = useRef<HTMLInputElement>(null);
    // Inside your component
    const [frameworks, setFrameworks] = useState([]);
    const [value, setValue] = useState("");
    const [open, setOpen] = useState(false);

    useEffect(() => {
      axios.get("/emr/list")
        .then(response => {
          setFrameworks(response.data);
        })
        .catch(error => {
          console.error("Error fetching frameworks:", error);
        });
    }, []);

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
          setValue(null);

        },
      });
    };

    return (
      <div className="w-full ml-2 mt-2 mr-3">
        <Head title="Register" />
        <div className="flex items-center mb-2">
          
          {user ? <Edit size={18} /> : <User size={18} />}
          <h1 className="text-lg font-semibold ml-2">{user ? 'Edit User' : 'Create User'}</h1>
        </div>
        <HeadingSmall title="Profile information" description="Enter your details below to create your account" />
        <div className="mb-3"></div>
        <form className="flex flex-col gap-2 mt-2" onSubmit={submit}>
          <div className="grid gap-1">
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
                className="focus:ring focus:ring-indigo-300 text-sm py-1 px-1"
                aria-describedby={errors.name ? 'name-error' : undefined}
              />
              <InputError id="name-error" message={errors.name} aria-live="polite" className="text-xs text-red-500" />
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
              <InputError id="email-error" message={errors.email} aria-live="polite" className="text-xs text-red-500" />
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
              <InputError id="password_confirmation-error" message={errors.password_confirmation} aria-live="polite" className="text-xs text-red-500" />
            </div>

            <div className="grid gap-1">
    <Label htmlFor="provider-selector">Emr Provider</Label>

    {/* Hidden input to submit the selected value */}
    <input type="hidden" name="emr_id" id ="emr_id" value={value || ''} />

    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          id="provider-selector"
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className="w-full justify-between"
        >
          {value
            ? frameworks.find((f) => f.emr_id === value)?.emr_name
            : "Select provider..."}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>

      <PopoverContent className="w-100 p-0" id="provider-popover-content">
        <Command>
          <CommandInput placeholder="Search provider..." />
          <CommandList>
            <CommandEmpty>No provider found.</CommandEmpty>
            <CommandGroup>
              {frameworks.map((framework) => (
                <CommandItem
                  key={framework.emr_id}
                  value={framework.emr_name}
                  onSelect={() => {
                    setValue(framework.emr_id);         // Update internal state for display
                    setData("emr_id", framework.emr_id); // Sync with Inertia form data
                    setOpen(false);
                  }}
                >
                  <Check
                    className={cn(
                      "mr-2 h-4 w-4",
                      value === framework.emr_id ? "opacity-100" : "opacity-0"
                    )}
                  />
                  {framework.emr_name}
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>

    {/* Corrected error binding */}
    <InputError id="emr-error" message={errors.emr_id} aria-live="polite" className="text-xs text-red-500" />
  </div>


            {/* Submit and Cancel Buttons */}
            <div className="mt-4 flex justify-between gap-2">
              <Button
                type="submit"
                variant="outline"
                className="flex-1 flex justify-center items-center gap-2 border-1 border-green-600  text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                disabled={processing}
              >
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                <span>{processing ? 'Processing...' : <><Save size={12} /></>}</span>
              </Button>

              {/* Cancel Button (only shown when editing a role) */}
              {user && (
                <Button
                  type="button"
                       variant="outline"
                  onClick={onCancel} // Trigger the onCancel function passed from parent
                  className="flex-1 flex justify-center items-center gap-2 border-1 border-red-400  text-red-600 hover:bg-red-600 hover:text-white font-semibold py-2 rounded-md transition-all"
                >
                  {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                  <span>{processing ? 'Processing...' : <><X size={12} /></>}</span>
                </Button>
              )}
            </div>
          </div>
        </form>
      </div>
    );
  }
