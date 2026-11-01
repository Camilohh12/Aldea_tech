/**
 * Dibuja el código QR de cada pase del alumno.
 * El QR contiene solo el código único de la inscripción (qr_token).
 */
import QRCode from 'qrcode';

document.querySelectorAll('canvas[data-qr]').forEach((canvas) => {
    QRCode.toCanvas(canvas, canvas.dataset.qr, {
        width: 240,
        margin: 1,
        errorCorrectionLevel: 'M',
        color: { dark: '#26093f', light: '#ffffff' },
    }).catch(() => {
        canvas.replaceWith(Object.assign(document.createElement('p'), {
            className: 'text-sm text-red-600',
            textContent: 'No se pudo generar el código QR. Recarga la página.',
        }));
    });
});
