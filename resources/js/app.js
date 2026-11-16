import './bootstrap';

const sidebar = document.getElementById('app-sidebar');
if (sidebar) import('./navigation');
if (document.getElementById('asset-scanner')) import('./scanner');
if (document.getElementById('rfid-validator')) import('./rfid-validator');
