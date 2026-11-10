// Asks for the device position once, on Clock in, only when the chosen shift
// or site is covered by the approved geofence. Saying no still clocks in.
export function initClockForm() {
    const form = document.querySelector('[data-clock-form]');
    if (!form || !('geolocation' in navigator)) {
        return;
    }

    const select = form.querySelector('select[name="target"]');
    const latitude = form.querySelector('[data-geofence-latitude]');
    const longitude = form.querySelector('[data-geofence-longitude]');
    let asked = false;

    form.addEventListener('submit', (event) => {
        const option = select?.selectedOptions[0];
        if (asked || !option || !option.hasAttribute('data-geofence')) {
            return;
        }

        event.preventDefault();
        asked = true;

        const submit = () => form.submit();
        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitude.value = position.coords.latitude.toFixed(6);
                longitude.value = position.coords.longitude.toFixed(6);
                submit();
            },
            submit,
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
    });
}
