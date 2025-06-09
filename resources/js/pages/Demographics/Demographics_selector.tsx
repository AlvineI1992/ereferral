import React, { useState, useEffect, useMemo } from 'react'
import {
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem
} from '@/components/ui/select'
import axios from 'axios'

type Barangay = { code: string; name: string }
type City = { code: string; name: string; barangays: Barangay[] }
type Province = { code: string; name: string; cities: City[] }
type Region = { code: string; name: string; provinces: Province[] }

type Props = {
  variant?: 'horizontal' | 'vertical',
  value?: {
    region?: string;
    province?: string;
    city?: string;
    barangay?: string;
  };
  onChange?: (val: {
    region?: string;
    province?: string;
    city?: string;
    barangay?: string;
  }) => void;
  canCreate: boolean;
  errors?: {
    region?: string;
    province?: string;
    city?: string;
    barangay?: string;
  };
}

export default function DemographicSelector({ variant = 'vertical', value, onChange, canCreate, errors }: Props) {
  const [data, setData] = useState<Region[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const [selectedRegion, setSelectedRegion] = useState<string | undefined>()
  const [selectedProvince, setSelectedProvince] = useState<string | undefined>()
  const [selectedCity, setSelectedCity] = useState<string | undefined>()
  const [selectedBarangay, setSelectedBarangay] = useState<string | undefined>()

  useEffect(() => {
    axios.get('/demographic/list')
      .then(res => {
        setData(res.data.regions as Region[])
      })
      .catch(err => {
        console.error('Failed to fetch demographic data:', err)
        setError('Unable to load demographic data. Please try again later.')
      })
      .finally(() => setLoading(false))
  }, [])

  useEffect(() => {
    if (!value) return

    if (value.region !== selectedRegion) {
      setSelectedRegion(value.region || undefined)
      setSelectedProvince(undefined)
      setSelectedCity(undefined)
      setSelectedBarangay(undefined)
    } else if (value.province !== selectedProvince) {
      setSelectedProvince(value.province || undefined)
      setSelectedCity(undefined)
      setSelectedBarangay(undefined)
    } else if (value.city !== selectedCity) {
      setSelectedCity(value.city || undefined)
      setSelectedBarangay(undefined)
    } else if (value.barangay !== selectedBarangay) {
      setSelectedBarangay(value.barangay || undefined)
    }
  }, [value])

  const regionObj = useMemo(() => data.find(r => r.code === selectedRegion), [data, selectedRegion])
  const provinceObj = useMemo(() => regionObj?.provinces.find(p => p.code === selectedProvince), [regionObj, selectedProvince])
  const cityObj = useMemo(() => provinceObj?.cities.find(c => c.code === selectedCity), [provinceObj, selectedCity])

  const provinces = regionObj?.provinces || []
  const cities = provinceObj?.cities || []
  const barangays = cityObj?.barangays || []

  const containerClass = variant === 'horizontal' ? 'grid grid-cols-4 gap-1 items-center' : 'space-y-1'
  const fieldClass = 'flex flex-col'

  if (loading) {
    return (
      <div className="relative">
        <div className="absolute right-0 flex items-center space-x-2 text-gray-600">
          <svg
            className="animate-spin h-5 w-5 text-blue-500"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
          </svg>
          <p className="text-sm font-medium">Loading Demographics...</p>
        </div>
        <div className="opacity-50 pointer-events-none">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-10 bg-gray-200 rounded mb-2"></div>
          ))}
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="p-4 rounded-md bg-red-50 text-red-700 border border-red-300">
        {error}
      </div>
    )
  }

  return (
    <div className={containerClass}>
      {/* Region */}
      <div className={fieldClass}>
      <div className="relative mt-1">
      
        <Select
          value={selectedRegion}
          onValueChange={(value) => {
            setSelectedRegion(value)
            setSelectedProvince(undefined)
            setSelectedCity(undefined)
            setSelectedBarangay(undefined)
            onChange?.({
              region: value,
              province: undefined,
              city: undefined,
              barangay: undefined,
            })
          }}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select Region" />
          </SelectTrigger>
          <SelectContent>
            {data.length === 0 && (
              <p className="px-2 py-1 text-sm text-gray-500">No regions available</p>
            )}
            {data.map(region => (
              <SelectItem key={region.code} value={region.code}>
                {region.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        {errors?.region && (
          <p className="text-[10px] text-red-500 mt-1">{errors.region}</p>
        )}
      </div>
      </div>
      {/* Province */}
      <div className={fieldClass}>
       
        <Select
          value={selectedProvince}
          onValueChange={(value) => {
            setSelectedProvince(value)
            setSelectedCity(undefined)
            setSelectedBarangay(undefined)
            onChange?.({
              region: selectedRegion,
              province: value,
              city: undefined,
              barangay: undefined,
            })
          }}
          disabled={!selectedRegion}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select Province" />
          </SelectTrigger>
          <SelectContent>
            {provinces.length === 0 && (
              <p className="px-2 py-1 text-sm text-gray-500">No provinces available</p>
            )}
            {provinces.map(province => (
              <SelectItem key={province.code} value={province.code}>
                {province.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        {errors?.province && (
          <p className="text-[10px] text-red-500 mt-1">{errors.province}</p>
        )}
      </div>

      {/* City */}
      <div className={fieldClass}>
      
        <Select
          value={selectedCity}
          onValueChange={(value) => {
            setSelectedCity(value)
            setSelectedBarangay(undefined)
            onChange?.({
              region: selectedRegion,
              province: selectedProvince,
              city: value,
              barangay: undefined,
            })
          }}
          disabled={!selectedProvince}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select City" />
          </SelectTrigger>
          <SelectContent>
            {cities.length === 0 && (
              <p className="px-2 py-1 text-sm text-gray-500">No cities available</p>
            )}
            {cities.map(city => (
              <SelectItem key={city.code} value={city.code}>
                {city.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        {errors?.city && (
          <p className="text-[10px] text-red-500 mt-1">{errors.city}</p>
        )}
      </div>

      {/* Barangay */}
      <div className={fieldClass}>
     
        <Select
          value={selectedBarangay}
          onValueChange={(value) => {
            setSelectedBarangay(value)
            onChange?.({
              region: selectedRegion,
              province: selectedProvince,
              city: selectedCity,
              barangay: value,
            })
          }}
          disabled={!selectedCity}
        >
          <SelectTrigger>
            <SelectValue placeholder="Select Barangay" />
          </SelectTrigger>
          <SelectContent>
            {barangays.length === 0 && (
              <p className="px-2 py-1 text-sm text-gray-500">No barangays available</p>
            )}
            {barangays.map(barangay => (
              <SelectItem key={barangay.code} value={barangay.code}>
                {barangay.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        {errors?.barangay && (
          <p className="text-[10px] text-red-500 mt-1">{errors.barangay}</p>
        )}
      </div>
    </div>
  )
}