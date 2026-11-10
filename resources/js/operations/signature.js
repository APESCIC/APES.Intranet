// Signature pad for form signature fields. The drawing is sent as a PNG data
// URL; the typed-name box next to it is the keyboard alternative.
export function initSignaturePads() {
    document.querySelectorAll('[data-signature-field]').forEach((field) => {
        const canvas = field.querySelector('[data-signature-pad]');
        const input = field.querySelector('[data-signature-input]');
        const clear = field.querySelector('[data-signature-clear]');
        if (!canvas || !input) {
            return;
        }

        const context = canvas.getContext('2d');
        context.lineWidth = 3;
        context.lineCap = 'round';
        context.strokeStyle = '#12262c';

        let drawing = false;
        let drawn = false;

        const point = (event) => {
            const box = canvas.getBoundingClientRect();
            return {
                x: ((event.clientX - box.left) / box.width) * canvas.width,
                y: ((event.clientY - box.top) / box.height) * canvas.height,
            };
        };

        canvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            const { x, y } = point(event);
            context.beginPath();
            context.moveTo(x, y);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!drawing) {
                return;
            }
            const { x, y } = point(event);
            context.lineTo(x, y);
            context.stroke();
            drawn = true;
        });

        const stop = () => {
            if (drawing && drawn) {
                input.value = canvas.toDataURL('image/png');
            }
            drawing = false;
        };
        canvas.addEventListener('pointerup', stop);
        canvas.addEventListener('pointercancel', stop);

        clear?.addEventListener('click', () => {
            context.clearRect(0, 0, canvas.width, canvas.height);
            input.value = '';
            drawn = false;
        });
    });
}
