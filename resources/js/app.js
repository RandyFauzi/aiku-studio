import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import './shifts';
import './attendance';
import './record-streak';
import './recording-matching';
import './recording-recommendations';
import './my-recordings';
import './slip-archive';
import './work-reports';
import './report-task-picker';
import './barcode-wifi';

window.Swal = Swal;
window.Alpine = Alpine;
window.loadQRCode = async () => {
    if (!window.QRCode) {
        window.QRCode = (await import('qrcode')).default;
    }
    return window.QRCode;
};
window.loadHtml5Qrcode = async () => {
    if (!window.Html5Qrcode) {
        window.Html5Qrcode = (await import('html5-qrcode')).Html5Qrcode;
    }
    return window.Html5Qrcode;
};
Alpine.start();
