import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Save, User, X } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import HeadingSmall from '@/components/heading-small';


type Props = {
  onCreated: () => void;
  onCancel: () => void;  // onCancel prop to handle the cancel action
  emr?: any;  // User object for editing (optional)
};

export default function Form({ onCreated, onCancel, emr }: Props) {
  const { data, setData, post, processing, errors, reset } = useForm({
    emr_name: emr?.emr_name || '',
    status: emr?.status || '',
    remarks: emr?.remarks || ''
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
        onCreated();
      },
    });
  };

  return (
    <div className="w-full ml-2 mt-2 mr-3">
      <Head title="Register" />
      <div className="flex items-center mb-2">
        <User size={18} />
        <h1 className="text-lg font-semibold text-gray-800 ml-2">{emr ? 'Edit Provider' : 'Create Provider'}</h1>
      </div>
      <HeadingSmall title="Provider Information" description="Enter your details below." />
      <div className="mb-4"></div>
      <form className="flex flex-col gap-4 mt-4" onSubmit={submit}>
        <div className="grid gap-4">

          <Label htmlFor="name">Name:</Label>
          <Input
            id="emr_name"
            ref={nameInputRef}
            value={data.emr_name}
            onChange={handleChange}
            className="mt-1 block w-full"
            autoComplete="emr_name"
          />
          <InputError message={errors.emr_name} className="mt-1" />

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
                <span>{processing ? 'Processing...' : <><X size={12} /></>}</span>
              </Button>
            )}
          </div>
        </div>
      </form>
    </div>
  );
}
