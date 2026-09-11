import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import { loadGoogleMaps, type MapsApi } from '@/lib/google-maps';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

type Props = { latitude: string; longitude: string; search: string; disabled: boolean; errors: { latitude?: string; longitude?: string }; onChange: (latitude: string, longitude: string) => void };

export default function FacilityLocationPicker({ latitude, longitude, search, disabled, errors, onChange }: Props) {
    const { googleMaps } = usePage<{ googleMaps?: { key?: string; map_id?: string } }>().props;
    const container = useRef<HTMLDivElement>(null);
    const map = useRef<InstanceType<MapsApi['Map']> | null>(null);
    const marker = useRef<InstanceType<MapsApi['marker']['AdvancedMarkerElement']> | null>(null);
    const change = useRef(onChange);
    const editable = useRef(!disabled);
    const [mapReady, setMapReady] = useState(false);
    const [error, setError] = useState('');
    const [locating, setLocating] = useState(false);
    useEffect(() => { change.current = onChange; editable.current = !disabled; }, [onChange, disabled]);
    const hasPosition = latitude.trim() !== '' && longitude.trim() !== '' && Number.isFinite(Number(latitude)) && Number.isFinite(Number(longitude)) && Math.abs(Number(latitude)) <= 90 && Math.abs(Number(longitude)) <= 180;

    useEffect(() => {
        if (!googleMaps?.key) return;
        let cancelled = false;
        let listener: { remove: () => void } | undefined;
        loadGoogleMaps(googleMaps.key).then((api) => {
            if (cancelled || !container.current) return;
            map.current = new api.Map(container.current, { center: { lat: 12.8797, lng: 121.774 }, zoom: 6, mapId: googleMaps.map_id || 'DEMO_MAP_ID', streetViewControl: false });
            marker.current = new api.marker.AdvancedMarkerElement({ map: map.current, title: 'Facility location' });
            listener = map.current.addListener('click', (event) => {
                if (editable.current && event.latLng) change.current(event.latLng.lat().toFixed(7), event.latLng.lng().toFixed(7));
            });
            setMapReady(true);
        }).catch((reason: Error) => { if (!cancelled) setError(reason.message); });
        return () => { cancelled = true; listener?.remove(); if (marker.current) marker.current.map = null; map.current = null; };
    }, [googleMaps?.key, googleMaps?.map_id]);

    useEffect(() => {
        if (!mapReady || !map.current || !marker.current) return;
        if (!hasPosition) { marker.current.position = null; return; }
        const position = { lat: Number(latitude), lng: Number(longitude) };
        marker.current.position = position;
        map.current.panTo(position);
        map.current.setZoom(17);
    }, [latitude, longitude, hasPosition, mapReady]);

    const locate = () => {
        if (!navigator.geolocation) { setError('Location is unavailable in this browser. Enter coordinates manually.'); return; }
        setLocating(true);
        setError('');
        navigator.geolocation.getCurrentPosition(({ coords }) => {
            if (editable.current) change.current(coords.latitude.toFixed(7), coords.longitude.toFixed(7));
            setLocating(false);
        }, () => { setError('Unable to get your location. Allow location access or enter coordinates manually.'); setLocating(false); }, { enableHighAccuracy: true, timeout: 15000 });
    };
    const query = hasPosition ? `${latitude},${longitude}` : `${search || 'Health facility'}, Philippines`;
    return <section className="mt-3 space-y-3 rounded-lg border p-3">
        <div><h2 className="text-sm font-semibold">Google Maps location</h2><p className="text-muted-foreground text-xs">Set the facility location. Use your current location only when you are at the facility.</p></div>
        {googleMaps?.key ? <><div ref={container} className="h-64 w-full rounded-md" aria-label="Facility location map" /><p className="text-muted-foreground text-xs">Click the map to place the facility pin.</p></> : <p className="text-muted-foreground text-xs">Open Google Maps to find the facility, then enter its coordinates below.</p>}
        <div className="flex flex-wrap gap-2">
            <Button asChild variant="outline" size="sm"><a href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(query)}`} target="_blank" rel="noopener noreferrer">Open Google Maps</a></Button>
            <Button type="button" variant="outline" size="sm" disabled={disabled || locating} onClick={locate}>{locating ? 'Locating...' : 'Use current location'}</Button>
            <Button type="button" variant="ghost" size="sm" disabled={disabled || (!latitude && !longitude)} onClick={() => onChange('', '')}>Clear location</Button>
        </div>
        <div className="grid grid-cols-2 gap-2">
            <div className="space-y-1"><Label htmlFor="latitude">Latitude</Label><Input id="latitude" type="number" step="any" min="-90" max="90" value={latitude} disabled={disabled} onChange={(event) => onChange(event.target.value, longitude)} /><InputError message={errors.latitude} /></div>
            <div className="space-y-1"><Label htmlFor="longitude">Longitude</Label><Input id="longitude" type="number" step="any" min="-180" max="180" value={longitude} disabled={disabled} onChange={(event) => onChange(latitude, event.target.value)} /><InputError message={errors.longitude} /></div>
        </div>
        {error && <p role="alert" className="text-destructive text-xs">{error}</p>}
    </section>;
}
