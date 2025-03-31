import AppLogoIcon from './app-logo-icon';
export default function AppLogo() {
    return (
        <>
            <img
                src="/doh-logo.png" 
                alt="App Logo"
                className="size-8 rounded-md"
            />
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-none font-semibold"> {import.meta.env.VITE_APP_NAME}</span>
            </div>
        </>
    );
}
