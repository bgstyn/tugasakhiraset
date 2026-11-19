import './bootstrap';
import './theme';
import './notifications';

const sidebar = document.getElementById('app-sidebar');
if (sidebar) import('./navigation');
if (document.getElementById('asset-scanner')) import('./scanner');
if (document.getElementById('rfid-validator')) import('./rfid-validator');
if (document.getElementById('pair-rfid-modal')) import('./rfid-pair');
if (document.getElementById('raspi-device-status')) import('./raspi-status');
