import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Hospital } from 'lucide-react';

export type HospitalOption = {
    hfhudcode: string;
    facility_name: string;
};

type HospitalSelectorProps = {
    hospitals?: HospitalOption[];
    selectedHospital: string;
    setSelectedHospital: (hfhudcode: string) => void;
    hospitalPopoverOpen: boolean;
    setHospitalPopoverOpen: (open: boolean) => void;
    placeholder?: string;
    error?: string;
    disabled?: boolean;
};

export default function HospitalSelector({
    hospitals = [],
    selectedHospital,
    setSelectedHospital,
    hospitalPopoverOpen,
    setHospitalPopoverOpen,
    placeholder = 'Select facility...',
    error,
    disabled = false,
}: HospitalSelectorProps) {
    const selected = hospitals.find((hospital) => hospital.hfhudcode === selectedHospital);

    return (
        <div className="grid gap-1">
            <Popover open={hospitalPopoverOpen} onOpenChange={setHospitalPopoverOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="hospital-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={hospitalPopoverOpen}
                        disabled={disabled}
                        className={cn(
                            'h-12 w-full justify-between rounded-md px-3 text-left font-normal',
                            error && 'border-destructive focus-visible:ring-destructive/30',
                        )}
                    >
                        <span className="flex min-w-0 items-center gap-2">
                            <Hospital className="text-muted-foreground size-4 shrink-0" />
                            <span className={cn('truncate', !selected && 'text-muted-foreground')}>
                                {selected ? `${selected.facility_name} (${selected.hfhudcode})` : placeholder}
                            </span>
                        </span>
                        <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0">
                    <Command>
                        <CommandInput placeholder="Search facility or code..." />
                        <CommandList>
                            <CommandEmpty>No facility found.</CommandEmpty>
                            <CommandGroup>
                                {hospitals.map((hospital) => (
                                    <CommandItem
                                        key={hospital.hfhudcode}
                                        value={`${hospital.facility_name} ${hospital.hfhudcode}`}
                                        onSelect={() => {
                                            setSelectedHospital(hospital.hfhudcode);
                                            setHospitalPopoverOpen(false);
                                        }}
                                    >
                                        <Check
                                            className={cn('mr-2 h-4 w-4', selectedHospital === hospital.hfhudcode ? 'opacity-100' : 'opacity-0')}
                                        />
                                        <div className="min-w-0">
                                            <p className="truncate">{hospital.facility_name}</p>
                                            <p className="text-muted-foreground text-xs">{hospital.hfhudcode}</p>
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {error && <p className="text-destructive mt-1 text-[11px]">{error}</p>}
        </div>
    );
}
