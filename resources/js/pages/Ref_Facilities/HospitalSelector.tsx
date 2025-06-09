import React from 'react';
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import { Command, CommandInput, CommandList, CommandEmpty, CommandGroup, CommandItem } from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils'; // Assuming you use a utility like clsx or cn
/* import InputError from '@/components/InputError'; // Adjust the import path if needed */

export type Hospital = {
    hfhudcode: string;
    facility_name: string;
};

export type HospitalSelectorProps = {
    label?: string;
    hospitals: Hospital[];
    selectedHospital: string;
    setSelectedHospital: (hfhudcode: string) => void;
    setData: (field: string, value: any) => void;
    hospitalPopoverOpen: boolean;
    setHospitalPopoverOpen: (open: boolean) => void;
    errors?: {
        hfhudcode?: string;
        [key: string]: string | undefined;
    };
};

const HospitalSelector:React.FC<HospitalSelectorProps>= ({
    label = 'Hospital',
    hospitals = [],
    selectedHospital,
    setSelectedHospital,
    setData,
    hospitalPopoverOpen,
    setHospitalPopoverOpen,
    errors = {}
}) => {
    return (
        <div className="grid gap-1">
         
            <Popover open={hospitalPopoverOpen} onOpenChange={setHospitalPopoverOpen}>
                <PopoverTrigger asChild>
                    <Button
                        id="hospital-selector"
                        variant="outline"
                        role="combobox"
                        aria-expanded={hospitalPopoverOpen}
                        className="w-full justify-between"
                    >
                        {hospitals.find(f => f.hfhudcode === selectedHospital)?.facility_name || 'Select hospital...'}
                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-100 p-0">
                    <Command>
                        <CommandInput placeholder="Search hospital..." />
                        <CommandList>
                            <CommandEmpty>No hospital found.</CommandEmpty>
                            <CommandGroup>
                                {hospitals.map(hospital => (
                                    <CommandItem
                                        key={hospital.hfhudcode}
                                        value={hospital.facility_name}
                                        onSelect={() => {
                                            setSelectedHospital(hospital.hfhudcode);
                                            setData('access_id', hospital.hfhudcode);
                                            setHospitalPopoverOpen(false);
                                        }}
                                    >
                                        <Check className={cn('mr-2 h-4 w-4', selectedHospital === hospital.hfhudcode ? 'opacity-100' : 'opacity-0')} />
                                        {hospital.facility_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        
        </div>
    );
};

export default HospitalSelector;
