type Position = { lat: number; lng: number };
type MapInstance = { panTo: (position: Position) => void; setZoom: (zoom: number) => void; addListener: (event: string, callback: (event: { latLng?: { lat: () => number; lng: () => number } }) => void) => { remove: () => void } };
type MarkerInstance = { position: Position | null; map: MapInstance | null };
export type MapsApi = {
    Map: new (element: HTMLElement, options: { center: Position; zoom: number; mapId: string; streetViewControl: boolean }) => MapInstance;
    marker: { AdvancedMarkerElement: new (options: { map: MapInstance; position?: Position; title: string }) => MarkerInstance };
};
let loading: Promise<MapsApi> | undefined;

export function loadGoogleMaps(key: string): Promise<MapsApi> {
    if (loading) return loading;
    loading = new Promise<MapsApi>((resolve, reject) => {
        const mapsWindow = window as typeof window & { google?: { maps: MapsApi }; facilityMapsReady?: () => void };
        const script = document.createElement('script');
        const timer = window.setTimeout(() => reject(new Error('Google Maps did not load. You can still enter coordinates below.')), 20000);
        mapsWindow.facilityMapsReady = () => {
            window.clearTimeout(timer);
            if (mapsWindow.google) resolve(mapsWindow.google.maps);
            else reject(new Error('Google Maps is unavailable.'));
        };
        script.src = `https://maps.googleapis.com/maps/api/js?${new URLSearchParams({ key, v: 'weekly', libraries: 'marker', loading: 'async', callback: 'facilityMapsReady' })}`;
        script.async = true;
        script.onerror = () => { window.clearTimeout(timer); reject(new Error('Unable to load Google Maps. You can still enter coordinates below.')); };
        document.head.appendChild(script);
    });
    return loading;
}
