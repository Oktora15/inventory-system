import './bootstrap';
import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';

document.querySelectorAll('[data-barcode-value]').forEach((element) => {
    try {
        JsBarcode(element, element.dataset.barcodeValue, {
            format: 'CODE128',
            displayValue: true,
            fontSize: 13,
            height: 54,
            margin: 4,
        });
    } catch (error) {
        const status = element.closest('.print-label, .product-codes')?.querySelector('[data-code-error]');
        if (status) {
            status.textContent = 'Barcode tidak dapat dibuat. Periksa SKU produk.';
        }
        console.error('Gagal membuat barcode produk.', error);
    }
});

document.querySelectorAll('[data-qr-value]').forEach((element) => {
    QRCode.toCanvas(element, element.dataset.qrValue, {
        width: 144,
        margin: 1,
        errorCorrectionLevel: 'M',
    }).catch((error) => {
        const status = element.closest('.print-label, .product-codes')?.querySelector('[data-code-error]');
        if (status) {
            status.textContent = 'QR Code tidak dapat dibuat.';
        }
        console.error('Gagal membuat QR Code produk.', error);
    });
});

const scannerForm = document.querySelector('[data-scanner-form]');
const scannerInput = scannerForm?.querySelector('[name="barcode"]');
const cameraButton = document.querySelector('[data-camera-start]');
const cameraStopButton = document.querySelector('[data-camera-stop]');
const cameraVideo = document.querySelector('[data-camera-video]');
const cameraStatus = document.querySelector('[data-camera-status]');

if (scannerForm && scannerInput && cameraButton && cameraVideo && cameraStatus) {
    let reader = null;
    let cameraControls = null;

    const stopCamera = () => {
        cameraControls?.stop();
        cameraControls = null;
        cameraVideo.hidden = true;
        if (cameraStopButton) {
            cameraStopButton.hidden = true;
        }
        cameraButton.hidden = false;
    };

    cameraButton.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            cameraStatus.textContent = 'Kamera tidak didukung. Gunakan scanner USB atau input SKU.';
            return;
        }

        cameraButton.disabled = true;
        cameraStatus.textContent = 'Meminta akses kamera...';
        cameraVideo.hidden = false;

        try {
            const { BrowserMultiFormatReader } = await import('@zxing/browser');
            reader ??= new BrowserMultiFormatReader();
            cameraControls = await reader.decodeFromVideoDevice(undefined, cameraVideo, (result, error) => {
                if (result) {
                    scannerInput.value = result.getText();
                    cameraStatus.textContent = 'Kode terbaca. Membuka produk...';
                    stopCamera();
                    scannerForm.requestSubmit();
                    return;
                }

                if (error && error.name !== 'NotFoundException') {
                    cameraStatus.textContent = `Pemindaian gagal: ${error.message}`;
                }
            });

            cameraStatus.textContent = 'Arahkan kamera ke barcode atau QR Code.';
            cameraButton.hidden = true;
            if (cameraStopButton) {
                cameraStopButton.hidden = false;
            }
        } catch (error) {
            stopCamera();
            cameraStatus.textContent = `Kamera tidak dapat digunakan: ${error.message}`;
        } finally {
            cameraButton.disabled = false;
        }
    });

    cameraStopButton?.addEventListener('click', () => {
        stopCamera();
        cameraStatus.textContent = 'Pemindaian kamera dihentikan.';
    });

    window.addEventListener('pagehide', stopCamera, { once: true });
}

document.querySelectorAll('[data-print-action]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});
