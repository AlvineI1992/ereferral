import * as React from 'react';
import { cn } from '@/lib/utils';

// ─── FloatingInput ────────────────────────────────────────────────────────────
type FloatingInputProps = React.InputHTMLAttributes<HTMLInputElement> & {
  label: string;
  error?: string;
  hint?: string;
  success?: boolean;
};

const FloatingInput = React.forwardRef<HTMLInputElement, FloatingInputProps>(
  ({ label, error, hint, success, className, id, ...props }, ref) => {
    const inputId = id ?? label.toLowerCase().replace(/\s+/g, '-');
    const isRequired = Boolean(props.required);

    return (
      <div className="relative w-full">
        <input
          id={inputId}
          ref={ref}
          placeholder=" "
          className={cn(
            'peer w-full rounded-md border bg-background px-1   pb-2 pt-3 text-sm text-foreground outline-none transition-shadow',
            'placeholder-transparent',
            'focus:ring-2 focus:ring-ring focus:ring-offset-0',
            error
              ? 'border-destructive focus:ring-destructive/30'
              : success
              ? 'border-green-500 focus:ring-green-500/30'
              : 'border-input hover:border-ring/60 focus:border-ring',
            className,
          )}
          {...props}
        />
        <label
          htmlFor={inputId}
          className={cn(
            'pointer-events-none absolute left-3 top-3.5 origin-[0] text-sm transition-all duration-150',
            'peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-muted-foreground',
            'peer-focus:top-1.5 peer-focus:text-[10px] peer-focus:font-medium peer-focus:tracking-wide',
            'peer-not-placeholder-shown:top-1.5 peer-not-placeholder-shown:text-[10px] peer-not-placeholder-shown:font-medium peer-not-placeholder-shown:tracking-wide',
            error
              ? 'text-destructive peer-focus:text-destructive'
              : success
              ? 'text-green-600 peer-focus:text-green-600'
              : 'text-muted-foreground peer-focus:text-foreground',
          )}
        >
          {label}
          {isRequired ? ' *' : ''}
        </label>
        {error && <p className="mt-1 px-0.5 text-[11px] text-destructive">{error}</p>}
        {hint && !error && <p className="mt-1 px-0.5 text-[11px] text-muted-foreground">{hint}</p>}
      </div>
    );
  },
);
FloatingInput.displayName = 'FloatingInput';

// ─── FloatingSelect ───────────────────────────────────────────────────────────
type FloatingSelectProps = React.SelectHTMLAttributes<HTMLSelectElement> & {
  label: string;
  error?: string;
  hint?: string;
  options: { value: string; label: string }[];
};

const FloatingSelect = React.forwardRef<HTMLSelectElement, FloatingSelectProps>(
  ({ label, error, hint, options, className, id, value, onChange, ...props }, ref) => {
    const selectId = id ?? label.toLowerCase().replace(/\s+/g, '-');
    const hasValue = value !== '' && value !== undefined;
    const isRequired = Boolean(props.required);

    return (
      <div className="relative w-full">
        <select
          id={selectId}
          ref={ref}
          value={value}
          onChange={onChange}
          className={cn(
            'peer w-full appearance-none rounded-md border bg-background px-3 pb-2 pt-5 text-sm text-foreground outline-none transition-shadow',
            'focus:ring-2 focus:ring-ring focus:ring-offset-0',
            error
              ? 'border-destructive focus:ring-destructive/30'
              : 'border-input hover:border-ring/60 focus:border-ring',
            className,
          )}
          {...props}
        >
          <option value="" disabled hidden />
          {options.map((opt) => (
            <option key={opt.value} value={opt.value}>
              {opt.label}
            </option>
          ))}
        </select>

        {/* Chevron icon */}
        <span className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
            <polyline points="6 9 12 15 18 9" />
          </svg>
        </span>

        <label
          htmlFor={selectId}
          className={cn(
            'pointer-events-none absolute left-3 origin-[0] text-sm transition-all duration-150',
            hasValue
              ? 'top-1.5 text-[10px] font-medium tracking-wide text-foreground'
              : 'top-3.5 text-muted-foreground',
            'peer-focus:top-1.5 peer-focus:text-[10px] peer-focus:font-medium peer-focus:tracking-wide peer-focus:text-foreground',
            error ? 'text-destructive peer-focus:text-destructive' : '',
          )}
        >
          {label}
          {isRequired ? ' *' : ''}
        </label>

        {error && <p className="mt-1 px-0.5 text-[11px] text-destructive">{error}</p>}
        {hint && !error && <p className="mt-1 px-0.5 text-[11px] text-muted-foreground">{hint}</p>}
      </div>
    );
  },
);
FloatingSelect.displayName = 'FloatingSelect';

// ─── FloatingTextarea ─────────────────────────────────────────────────────────
type FloatingTextareaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement> & {
  label: string;
  error?: string;
  hint?: string;
};

const FloatingTextarea = React.forwardRef<HTMLTextAreaElement, FloatingTextareaProps>(
  ({ label, error, hint, className, id, ...props }, ref) => {
    const textareaId = id ?? label.toLowerCase().replace(/\s+/g, '-');
    const isRequired = Boolean(props.required);

    return (
      <div className="relative w-full">
        <textarea
          id={textareaId}
          ref={ref}
          placeholder=" "
          className={cn(
            'peer w-full resize-y rounded-md border bg-background px-3 pb-2 pt-6 text-sm text-foreground outline-none transition-shadow',
            'placeholder-transparent leading-relaxed',
            'min-h-[100px]',
            'focus:ring-2 focus:ring-ring focus:ring-offset-0',
            error
              ? 'border-destructive focus:ring-destructive/30'
              : 'border-input hover:border-ring/60 focus:border-ring',
            className,
          )}
          {...props}
        />
        <label
          htmlFor={textareaId}
          className={cn(
            'pointer-events-none absolute left-3 top-4 origin-[0] text-sm transition-all duration-150',
            'peer-placeholder-shown:top-4 peer-placeholder-shown:text-sm peer-placeholder-shown:text-muted-foreground',
            'peer-focus:top-1.5 peer-focus:text-[10px] peer-focus:font-medium peer-focus:tracking-wide peer-focus:text-foreground',
            'peer-not-placeholder-shown:top-1.5 peer-not-placeholder-shown:text-[10px] peer-not-placeholder-shown:font-medium peer-not-placeholder-shown:tracking-wide peer-not-placeholder-shown:text-foreground',
            error ? 'text-destructive peer-focus:text-destructive' : 'text-muted-foreground',
          )}
        >
          {label}
          {isRequired ? ' *' : ''}
        </label>
        {error && <p className="mt-1 px-0.5 text-[11px] text-destructive">{error}</p>}
        {hint && !error && <p className="mt-1 px-0.5 text-[11px] text-muted-foreground">{hint}</p>}
      </div>
    );
  },
);
FloatingTextarea.displayName = 'FloatingTextarea';

export { FloatingInput, FloatingSelect, FloatingTextarea };

// ─── Usage example (your ReferralForm) ───────────────────────────────────────
//
// import { FloatingInput, FloatingSelect, FloatingTextarea } from '@/components/ui/FloatingInput';
//
// <FloatingInput
//   label="Full name"
//   name="fullName"
//   value={data.fullName}
//   onChange={handleChange}
//   error={getError('fullName')}
// />
//
// <FloatingSelect
//   label="Type of referral"
//   value={data.typeOfReferral}
//   onChange={(e) => setData('typeOfReferral', e.target.value)}
//   error={getError('typeOfReferral')}
//   options={[
//     { value: 'CONSU', label: 'Consultation' },
//     { value: 'DIAGT', label: 'Diagnostic' },
//     { value: 'TRANS', label: 'Transfer' },
//     { value: 'OTHER', label: 'Others' },
//   ]}
// />
//
// <FloatingTextarea
//   label="Referral notes"
//   name="notes"
//   value={data.notes}
//   onChange={handleChange}
// />
