import type * as Leaflet from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useEffect, useRef, useState } from 'react';

export type HeatFacility = { code: string; name: string; count: number; latitude: number | null; longitude: number | null; mapped: boolean };
type HeatLeaflet = typeof Leaflet;
let library: Promise<HeatLeaflet> | undefined;
function loadMap() {
    library ??= import('leaflet').then(async (module) => {
        const L = module.default;
        (window as typeof window & { L: HeatLeaflet }).L = L;
        await import('leaflet.heat');
        return L;
    });
    return library;
}

export default function DiagnosisMap({ facilities }: { facilities: HeatFacility[] }) {
    const container = useRef<HTMLDivElement>(null);
    const map = useRef<Leaflet.Map | null>(null);
    const [ready, setReady] = useState(false);
    const [error, setError] = useState('');
    useEffect(() => {
        let cancelled = false;
        let resize: ResizeObserver | undefined;
        loadMap().then((L) => {
            if (cancelled || !container.current) return;
            map.current = L.map(container.current, { minZoom: 5, maxZoom: 18 }).fitBounds([[4.5, 116], [21, 127]]);
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', maxZoom: 19,
            }).on('tileerror', () => setError('Some map tiles could not load. Counts remain available in the table.')).addTo(map.current);
            resize = new ResizeObserver(() => map.current?.invalidateSize());
            resize.observe(container.current);
            setReady(true);
        }).catch(() => { if (!cancelled) setError('Unable to load the map. Counts remain available in the table.'); });
        return () => { cancelled = true; resize?.disconnect(); map.current?.remove(); map.current = null; };
    }, []);

    useEffect(() => {
        if (!ready || !map.current) return;
        let cancelled = false;
        let layers: Leaflet.LayerGroup | undefined;
        loadMap().then((L) => {
            if (cancelled || !map.current) return;
            layers = L.layerGroup().addTo(map.current);
            const points = facilities.filter((facility) => facility.mapped);
            const maximum = Math.max(1, ...points.map((facility) => facility.count));
            L.heatLayer(points.map((facility) => [facility.latitude!, facility.longitude!, facility.count / maximum] as [number, number, number]), {
                radius: 32, blur: 24, minOpacity: 0.35, maxZoom: 6, gradient: { 0.2: '#2563eb', 0.45: '#06b6d4', 0.65: '#facc15', 0.85: '#f97316', 1: '#dc2626' },
            }).addTo(layers);
            points.forEach((facility) => {
                const tooltip = document.createElement('span');
                tooltip.textContent = `${facility.name}: ${facility.count.toLocaleString()} discharged referrals`;
                L.circleMarker([facility.latitude!, facility.longitude!], { radius: 4, color: '#334155', weight: 1, fillOpacity: 0.2 }).bindTooltip(tooltip).addTo(layers!);
            });
        }).catch(() => { if (!cancelled) setError('Unable to draw the heat map. Counts remain available below.'); });
        return () => { cancelled = true; layers?.remove(); };
    }, [facilities, ready]);

    return <div className="space-y-2">
        <div ref={container} className="relative z-0 h-[520px] w-full rounded-lg border" aria-label="Philippines final diagnosis heat map" />
        <div className="flex items-center gap-2 text-xs"><span>Lower count</span><div className="h-2 w-40 rounded bg-gradient-to-r from-blue-600 via-yellow-400 to-red-600" /><span>Higher count</span></div>
        <p className="text-muted-foreground text-xs">Intensity is relative to the busiest facility in this selection. Nearby points blend together. This shows facility counts, not population-adjusted disease rates or patient residences.</p>
        {error && <p role="alert" className="text-destructive text-sm">{error}</p>}
    </div>;
}
