import React, { useState, useEffect, useMemo } from 'react'
import axios from 'axios'
import { FloatingSelect } from '@/components/ui/FloatingInput'

type Barangay = { code: string; name: string }
type City     = { code: string; name: string; barangays: Barangay[] }
type Province = { code: string; name: string; cities: City[] }
type Region   = { code: string; name: string; provinces: Province[] }

type Props = {
  variant?: 'horizontal' | 'vertical'
  value?: {
    region?:   string
    province?: string
    city?:     string
    barangay?: string
  }
  onChange?: (val: {
    region?:   string
    province?: string
    city?:     string
    barangay?: string
  }) => void
  canCreate: boolean
  errors?: {
    region?:   string
    province?: string
    city?:     string
    barangay?: string
  }
}

export default function DemographicSelector({
  variant = 'vertical',
  value,
  onChange,
  errors,
}: Props) {
  const [data, setData]       = useState<Region[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError]     = useState<string | null>(null)

  const [selectedRegion,   setSelectedRegion]   = useState<string>('')
  const [selectedProvince, setSelectedProvince] = useState<string>('')
  const [selectedCity,     setSelectedCity]     = useState<string>('')
  const [selectedBarangay, setSelectedBarangay] = useState<string>('')
  const regionValue = value?.region || ''
  const provinceValue = value?.province || ''
  const cityValue = value?.city || ''
  const barangayValue = value?.barangay || ''

  useEffect(() => {
    axios.get('/demographic/list')
      .then(res => setData(res.data.regions as Region[]))
      .catch(err => {
        console.error('Failed to fetch demographic data:', err)
        setError('Unable to load demographic data. Please try again later.')
      })
      .finally(() => setLoading(false))
  }, [])

  // Sync external value into local state
  useEffect(() => {
    setSelectedRegion(regionValue)
    setSelectedProvince(provinceValue)
    setSelectedCity(cityValue)
    setSelectedBarangay(barangayValue)
  }, [regionValue, provinceValue, cityValue, barangayValue])

  const regionObj   = useMemo(() => data.find(r => r.code === selectedRegion),               [data, selectedRegion])
  const provinceObj = useMemo(() => regionObj?.provinces.find(p => p.code === selectedProvince), [regionObj, selectedProvince])
  const cityObj     = useMemo(() => provinceObj?.cities.find(c => c.code === selectedCity),      [provinceObj, selectedCity])

  const provinces = regionObj?.provinces  || []
  const cities    = provinceObj?.cities   || []
  const barangays = cityObj?.barangays    || []

  const containerClass = variant === 'horizontal'
    ? 'grid grid-cols-4 gap-3 items-start'
    : 'space-y-3'

  // ── Loading state ──────────────────────────────────────────────────────────
  if (loading) {
    return (
      <div className="relative min-h-[160px]">
        <div className="absolute inset-0 flex flex-col items-center justify-center text-gray-600 space-y-2">
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
        <div className="opacity-50 pointer-events-none space-y-2">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="h-12 bg-gray-200 rounded" />
          ))}
        </div>
      </div>
    )
  }

  // ── Error state ────────────────────────────────────────────────────────────
  if (error) {
    return (
      <div className="p-4 rounded-md bg-red-50 text-red-700 border border-red-300 text-sm">
        {error}
      </div>
    )
  }

  // ── Main render ────────────────────────────────────────────────────────────
  return (
    <div className={containerClass}>

      {/* Region */}
      <FloatingSelect
        id="region"
        label="Region"
        required
        value={selectedRegion}
        onChange={(e) => {
          const val = e.target.value
          setSelectedRegion(val)
          setSelectedProvince('')
          setSelectedCity('')
          setSelectedBarangay('')
          onChange?.({ region: val, province: undefined, city: undefined, barangay: undefined })
        }}
        error={errors?.region}
        options={data.map(r => ({ value: r.code, label: r.name }))}
      />

      {/* Province */}
      <FloatingSelect
        id="province"
        label="Province"
        required
        value={selectedProvince}
        onChange={(e) => {
          const val = e.target.value
          setSelectedProvince(val)
          setSelectedCity('')
          setSelectedBarangay('')
          onChange?.({ region: selectedRegion, province: val, city: undefined, barangay: undefined })
        }}
        disabled={!selectedRegion}
        error={errors?.province}
        options={provinces.map(p => ({ value: p.code, label: p.name }))}
      />

      {/* City */}
      <FloatingSelect
        id="city"
        label="City / Municipality"
        required
        value={selectedCity}
        onChange={(e) => {
          const val = e.target.value
          setSelectedCity(val)
          setSelectedBarangay('')
          onChange?.({ region: selectedRegion, province: selectedProvince, city: val, barangay: undefined })
        }}
        disabled={!selectedProvince}
        error={errors?.city}
        options={cities.map(c => ({ value: c.code, label: c.name }))}
      />

      {/* Barangay */}
      <FloatingSelect
        id="barangay"
        label="Barangay"
        required
        value={selectedBarangay}
        onChange={(e) => {
          const val = e.target.value
          setSelectedBarangay(val)
          onChange?.({ region: selectedRegion, province: selectedProvince, city: selectedCity, barangay: val })
        }}
        disabled={!selectedCity}
        error={errors?.barangay}
        options={barangays.map(b => ({ value: b.code, label: b.name }))}
      />

    </div>
  )
}
