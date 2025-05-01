'use client'

import React, { useState, useEffect } from 'react'
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
}

export default function DemographicSelector({ variant = 'vertical', value, onChange }: Props) {
  const [data, setData] = useState<Region[]>([])
  const [loading, setLoading] = useState(true)

  const [selectedRegion, setSelectedRegion] = useState<string | undefined>()
  const [selectedProvince, setSelectedProvince] = useState<string | undefined>()
  const [selectedCity, setSelectedCity] = useState<string | undefined>()
  const [selectedBarangay, setSelectedBarangay] = useState<string | undefined>()

  useEffect(() => {
    axios.get('/demographic/list')
      .then(res => setData(res.data.regions))
      .catch(err => console.error('Failed to fetch demographic data:', err))
      .finally(() => setLoading(false))
  }, [])

  const regionObj = data.find(r => r.code === selectedRegion)
  const provinceObj = regionObj?.provinces.find(p => p.code === selectedProvince)
  const cityObj = provinceObj?.cities.find(c => c.code === selectedCity)

  const provinces = regionObj?.provinces || []
  const cities = provinceObj?.cities || []
  const barangays = cityObj?.barangays || []

  const containerClass = variant === 'horizontal' ? 'grid grid-cols-4 gap-1 items-center' : 'space-y-1'
  const fieldClass = 'flex flex-col'

  if (loading) {
    return (
      <div className="flex items-center space-x-2 text-gray-600">
        <svg
          className="animate-spin h-5 w-5 text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle
            className="opacity-25"
            cx="12"
            cy="12"
            r="10"
            stroke="currentColor"
            strokeWidth="4"
          />
          <path
            className="opacity-75"
            fill="currentColor"
            d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
          />
        </svg>
        <p className="text-sm font-medium">Loading Demographics...</p>
      </div>
    )
  }

  return (
    <div className={containerClass}>
      {/* Region */}
      <div className={fieldClass}>
        <label className="mb-1 text-sm font-medium">Region</label>
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
            {data.map(region => (
              <SelectItem key={region.code} value={region.code}>
                {region.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Province */}
      <div className={fieldClass}>
        <label className="mb-1 text-sm font-medium">Province</label>
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
            {provinces.map(province => (
              <SelectItem key={province.code} value={province.code}>
                {province.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* City */}
      <div className={fieldClass}>
        <label className="mb-1 text-sm font-medium">City</label>
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
            {cities.map(city => (
              <SelectItem key={city.code} value={city.code}>
                {city.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>

      {/* Barangay */}
      <div className={fieldClass}>
        <label className="mb-1 text-sm font-medium">Barangay</label>
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
            {barangays.map(barangay => (
              <SelectItem key={barangay.code} value={barangay.code}>
                {barangay.name}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      </div>
    </div>
  )
}
