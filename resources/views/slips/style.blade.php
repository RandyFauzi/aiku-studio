@page {
    margin: 28px 34px;
}
body {
    font-family: 'Inter', 'Plus Jakarta Sans', Helvetica, Arial, sans-serif;
    background-color: #ffffff;
    color: #111827;
    margin: 0;
    padding: 0;
    font-size: 11px;
    line-height: 1.45;
}
.page-break {
    page-break-before: always;
}
.gradient-header {
    background-image: url('/assets/robot-banner.webp');
    background-size: cover;
    background-position: center 20%;
    height: 140px;
    border-radius: 16px;
    margin-bottom: 28px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
@media print {
    body {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        margin: 0 !important;
        padding: 24px !important;
    }
    .evidence-card {
        page-break-inside: avoid;
        margin-bottom: 14px !important;
    }
    .evidence-task-card {
        page-break-inside: avoid;
    }
    .no-print {
        display: none !important;
    }
    .evidence-body {
        display: none !important;
    }
    .evidence-task-card {
        display: none !important;
    }
}

/* PDF (Dompdf) dedicated classes */
.pdf-container {
    width: 100%;
    color: #111827;
}
.pdf-banner {
    width: 100%;
    height: auto;
    display: block;
    margin-bottom: 22px;
}
.pdf-header-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 18px;
}
.pdf-logo-circle {
    width: 50px;
    height: 20px;
    padding: 15px 0;
    border-radius: 25px;
    background-color: #000000;
    color: #ffffff;
    font-size: 15px;
    font-weight: 800;
    text-align: center;
    line-height: 20px;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
}
.pdf-payslip-h1 {
    font-size: 44px;
    font-weight: 800;
    color: #000000;
    letter-spacing: -1px;
    line-height: 1;
    margin: 0 0 8px 0;
}
.pdf-slip-code {
    font-size: 12px;
    font-weight: 700;
    color: #6b7280;
    text-align: right;
}
.pdf-meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}
.pdf-meta-table td {
    width: 25%;
    vertical-align: top;
    padding-right: 12px;
    font-size: 11px;
    line-height: 1.5;
}
.pdf-meta-label {
    font-size: 9.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin: 0 0 6px 0;
}
.pdf-meta-strong {
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.pdf-meta-muted {
    color: #6b7280;
    margin: 0;
}
.pdf-items-wrap {
    border-top: 1px solid #e5e7eb;
    padding-top: 8px;
    margin-bottom: 24px;
}
.pdf-items-table {
    width: 100%;
    border-collapse: collapse;
}
.pdf-items-table th {
    font-size: 9.5px;
    font-weight: 700;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 8px 0 12px 0;
    border-bottom: 1px solid #e5e7eb;
}
.pdf-items-table td {
    padding: 13px 0;
    border-bottom: 1px solid #f3f4f6;
    font-size: 11px;
}
.pdf-totals-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}
.pdf-totals-inner {
    width: 100%;
    border-collapse: collapse;
}
.pdf-totals-inner td {
    padding: 6px 0;
    font-size: 11px;
    color: #4b5563;
}
.pdf-totals-inner tr.pdf-tax-row td {
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 8px;
}
.pdf-totals-inner tr.pdf-grand-row td {
    padding-top: 10px;
    font-size: 15px;
    font-weight: 800;
    color: #111827;
}
.pdf-footer-table {
    width: 100%;
    border-collapse: collapse;
    border-top: 1px solid #e5e7eb;
    margin-top: 22px;
    padding-top: 16px;
}
.pdf-footer-table td {
    width: 50%;
    vertical-align: top;
    padding-top: 14px;
    padding-right: 20px;
}
.pdf-summary-card {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: 14px;
}
.pdf-note-box {
    background-color: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 14px;
    color: #92400e;
    font-size: 10px;
    line-height: 1.45;
}
.pdf-day-card {
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 11px 14px;
    margin-bottom: 10px;
    page-break-inside: avoid;
}
.pdf-h-badge {
    display: inline-block;
    vertical-align: middle;
    background-color: #eef2ff;
    color: #4338ca;
    border: 1px solid #e0e7ff;
    border-radius: 7px;
    padding: 3px 7px;
    font-size: 10px;
    font-weight: 800;
    margin-right: 8px;
}
.pdf-day-title {
    display: inline-block;
    vertical-align: middle;
    font-size: 12px;
    font-weight: 800;
    color: #111827;
    margin-right: 6px;
}
.pdf-task-pill {
    display: inline-block;
    vertical-align: middle;
    background-color: #e5e7eb;
    color: #374151;
    border-radius: 10px;
    padding: 2px 7px;
    font-size: 9px;
    font-weight: 800;
}
.pdf-status-pill {
    display: inline-block;
    vertical-align: middle;
    border-radius: 10px;
    padding: 2px 9px;
    font-size: 9.5px;
    font-weight: 700;
    margin-right: 8px;
}
.pdf-status-full {
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}
.pdf-status-partial {
    background-color: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.pdf-status-rejected {
    background-color: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
}
.pdf-status-pending {
    background-color: #fefce8;
    color: #a16207;
    border: 1px solid #fef08a;
}
.pdf-stat-chip-appr {
    display: inline-block;
    background-color: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 9px;
    font-weight: 700;
    margin-right: 6px;
}
.pdf-stat-chip-rej {
    display: inline-block;
    background-color: #fff1f2;
    color: #be123c;
    border: 1px solid #fecdd3;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 9px;
    font-weight: 700;
    margin-right: 6px;
}
.pdf-stat-chip-pend {
    display: inline-block;
    background-color: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
    border-radius: 5px;
    padding: 2px 7px;
    font-size: 9px;
    font-weight: 700;
    margin-right: 6px;
}
