import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import QRCode from 'qrcode';
import { Html5Qrcode } from 'html5-qrcode';
import './shifts';
import './attendance';
import './record-streak';
import './recording-matching';
import './work-reports';
import './report-task-picker';
import './barcode-wifi';

window.Swal = Swal;
window.Alpine = Alpine;
window.QRCode = QRCode;
window.Html5Qrcode = Html5Qrcode;
Alpine.start();
