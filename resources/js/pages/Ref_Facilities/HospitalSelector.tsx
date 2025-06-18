import React, { useEffect, useState } from 'react';
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandInput,
    CommandList,
    CommandEmpty,
    CommandGroup,
    CommandItem,
} from '@/components/ui/command';
import { Label } from '@/components/ui/label';
import { Check, ChevronsUpDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import axios from 'axios';

export type Hospital = {
    hfhudcode: string;
    facility_name: string;
};

export type HospitalSelectorProps = {
    label?: string;
    hospitals?: Hospital[];
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

const HospitalSelector: React.FC<HospitalSelectorProps> = ({
    label = 'Hospital',
    hospitals = [],
    selectedHospital,
    setSelectedHospital,
    setData,
    hospitalPopoverOpen,
    setHospitalPopoverOpen,
    errors = {},
}) => {
    const [localHospitals, setLocalHospitals] = useState<Hospital[]>(hospitals);

    useEffect(() => {
        if (hospitals.length === 0) {
            axios
                .get('/facilities-list')
                .then((res) => setLocalHospitals(res.data.data))
                .catch((err) => console.error(err));
        }
    }, [hospitals]);

    const handleSelect = (facility_name: string) => {
        const selected = localHospitals.find(h => h.facility_name === facility_name);
        if (selected) {
            setSelectedHospital(selected.hfhudcode);
            setHospitalPopoverOpen(false);
        }
    };

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
                        {localHospitals.find(f => f.hfhudcode === selectedHospital)?.facility_name || 'Select hospital...'}
                        <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent className="w-full p-0">
                    <Command>
                        <CommandInput placeholder="Search hospital..." />
                        <CommandList>
                            <CommandEmpty>No hospital found.</CommandEmpty>
                            <CommandGroup>
                                {localHospitals.map((hospital) => (
                                    <CommandItem
                                        key={hospital.hfhudcode}
                                        value={hospital.facility_name}
                                        onSelect={handleSelect}
                                    >
                                        <Check
                                            className={cn(
                                                'mr-2 h-4 w-4',
                                                selectedHospital === hospital.hfhudcode ? 'opacity-100' : 'opacity-0'
                                            )}
                                        />
                                        {hospital.facility_name}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>

            {errors.hfhudcode && (
                <p className="text-sm text-red-600 mt-1">{errors.hfhudcode}</p>
            )}
        </div>
    );
};

export default HospitalSelector;
