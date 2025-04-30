import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Save, User, X } from 'lucide-react';
import { FormEventHandler, useEffect, useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import HeadingSmall from '@/components/heading-small';
import { Textarea } from '@/components/ui/textarea';
import { toast } from "sonner";

type Props = {
  onCreated: () => void;
  onCancel: () => void;
  emr?: any;
};

type Formtype = {
  emr_name: string;
  status: boolean;
  remarks: string;
};

export default function Form({ onCreated, onCancel, emr }: Props) {
  const { data, setData, post, processing, errors, reset } = useForm<Formtype>({
    emr_name: emr?.emr_name || '',
    status: !!emr?.status,
    remarks: emr?.remarks || ''
  });

  const nameInputRef = useRef<HTMLInputElement>(null);

  useEffect(() => {
    nameInputRef.current?.focus();
  }, []);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setData(e.target.id, e.target.value);
  };

  const handleSwitchChange = (checked: boolean) => {
    setData('status', checked);
  };

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('emr.store'), {
      onSuccess: () => {
        reset();
        onCreated();
        toast.success('Record saved!');
      },
    });
  };

  return (
    <div className="w-full ml-2 mt-2 mr-3">
      <Head title="Register" />
      <div className="flex items-center mb-2">
        <User size={18} />
        <h1 className="text-lg font-semibold text-gray-800 ml-2">
          {emr ? 'Edit Provider' : 'Create Provider'}
        </h1>
      </div>
      <HeadingSmall title="Provider Information" description="Enter your details below." />
      <form className="flex flex-col gap-4 mt-4" onSubmit={submit}>
        <div className="grid gap-4">
          {/* Name */}
          <Label htmlFor="emr_name">Name:</Label>
          <Input
            id="emr_name"
            ref={nameInputRef}
            value={data.emr_name}
            placeholder='Provider name'
            onChange={handleChange}
            className="mt-1 block w-full"
            autoComplete="off"
          />
          <InputError message={errors.emr_name} className="mt-1" />

          {/* Status */}
          <Label htmlFor="status" className="mb-2 block">Status:</Label>
          <Switch
            id="status"
            checked={data.status}
            onCheckedChange={handleSwitchChange}
          />
          <InputError message={errors.status} className="mt-1" />

          {/* Remarks */}
          <Label htmlFor="remarks">Remarks:</Label>
          <Textarea
            id="remarks"
            value={data.remarks}
            onChange={handleChange}
            className="mt-1 block w-full"
            placeholder='Remarks'
            autoComplete="off"
          />
          <InputError message={errors.remarks} className="mt-1" />

          {/* Buttons */}
          <div className="mt-4 flex justify-between gap-4">
            <Button
              type="submit"
              className="flex-1 flex justify-center items-center gap-2 border border-green-600 bg-white text-green-600 hover:bg-green-600 hover:text-white font-semibold py-2 rounded-md transition-all"
              disabled={processing}
            >
              {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
              <span>{processing ? 'Processing...' : <><Save size={12} /> </>}</span>
            </Button>

            {emr && (
              <Button
                type="button"
                onClick={onCancel}
                className="flex-1 flex justify-center items-center gap-2 border border-red-400 bg-white text-red-600 hover:bg-red-600 hover:text-white font-semibold py-2 rounded-md transition-all"
              >
                {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                <span>{processing ? 'Processing...' : <><X size={12} /> </>}</span>
              </Button>
            )}
          </div>
        </div>
      </form>
    </div>
  );
}
