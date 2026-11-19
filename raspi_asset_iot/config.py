"""Application settings. All pin numbers are BCM numbers."""
import os
import math
from dataclasses import dataclass
from pathlib import Path

from dotenv import load_dotenv

load_dotenv(Path(__file__).resolve().parent / '.env')


def _bool(name, default=False):
    return os.getenv(name, str(default)).strip().lower() in ('1', 'true', 'yes', 'on')


@dataclass(frozen=True)
class Config:
    api_base_url: str = os.getenv('API_BASE_URL', '').rstrip('/')
    api_token: str = os.getenv('API_TOKEN', '').strip()
    db_path: str = str(Path(__file__).resolve().parent / (os.getenv('DB_PATH') or 'data/queue.sqlite3'))
    lcd_address: int = int(os.getenv('LCD_ADDRESS', '0x27'), 0)
    lcd_enabled: bool = _bool('LCD_ENABLED', True)
    led_type: str = os.getenv('LED_TYPE', 'common_anode').strip().lower()
    led_enabled: bool = _bool('LED_ENABLED', False)  # enable after common/anode check
    led_red_gpio: int = int(os.getenv('LED_RED_GPIO', '5'))
    led_green_gpio: int = int(os.getenv('LED_GREEN_GPIO', '6'))
    led_blue_gpio: int = int(os.getenv('LED_BLUE_GPIO', '13'))
    switch_check_gpio: int = int(os.getenv('SWITCH_CHECK_GPIO', '17'))
    switch_register_gpio: int = int(os.getenv('SWITCH_REGISTER_GPIO', '27'))
    rfid_rst_gpio: int = int(os.getenv('RFID_RST_GPIO', '25'))
    rfid_spi_device: int = int(os.getenv('RFID_SPI_DEVICE', '0'))
    buzzer_gpio: int = int(os.getenv('BUZZER_GPIO', '18'))
    buzzer_enabled: bool = _bool('BUZZER_ENABLED', False)  # safe until buzzer specs checked
    rfid_uid_format: str = os.getenv('RFID_UID_FORMAT', 'hex').strip().lower()
    camera_index: int = int(os.getenv('CAMERA_INDEX', '0'))
    camera_enabled: bool = _bool('CAMERA_ENABLED', True)
    camera_stream_enabled: bool = _bool('CAMERA_STREAM_ENABLED', True)
    camera_stream_fps: int = int(os.getenv('CAMERA_STREAM_FPS', '5'))
    rfid_enabled: bool = _bool('RFID_ENABLED', True)
    scan_repeat_seconds: float = float(os.getenv('SCAN_REPEAT_SECONDS', '3.0'))
    pair_timeout_seconds: float = float(os.getenv('PAIR_TIMEOUT_SECONDS', '90'))
    http_timeout_seconds: float = float(os.getenv('HTTP_TIMEOUT_SECONDS', '6'))
    pending_limit: int = int(os.getenv('PENDING_LIMIT', '10000'))
    success_retention_days: int = int(os.getenv('SUCCESS_RETENTION_DAYS', '30'))
    scan_max_age_seconds: float = float(os.getenv('SCAN_MAX_AGE_SECONDS', '5'))
    worker_stall_seconds: float = float(os.getenv('WORKER_STALL_SECONDS', '30'))
    shutdown_timeout_seconds: float = float(os.getenv('SHUTDOWN_TIMEOUT_SECONDS', '8'))
    startup_check_seconds: float = float(os.getenv('STARTUP_CHECK_SECONDS', '8'))
    simulate: bool = _bool('SIMULATE', False)

    def validate(self):
        if not self.api_base_url.startswith(('https://', 'http://')):
            raise ValueError('API_BASE_URL harus berupa URL http(s) yang lengkap')
        if not self.api_token or self.api_token.startswith('GANTI_'):
            raise ValueError('Set API_TOKEN di .env sesuai RFID_API_TOKEN pada Laravel')
        if self.api_base_url.startswith('http://') and not _bool('ALLOW_INSECURE_HTTP', False):
            raise ValueError('Gunakan HTTPS untuk internet (atau ALLOW_INSECURE_HTTP=1 khusus LAN tes)')
        if self.led_type not in ('common_cathode', 'common_anode'):
            raise ValueError('LED_TYPE harus common_cathode atau common_anode')
        if self.rfid_uid_format not in ('hex', 'decimal'):
            raise ValueError('RFID_UID_FORMAT harus hex atau decimal')
        if self.pending_limit < 1:
            raise ValueError('PENDING_LIMIT harus >=1')
        for name, value, minimum, maximum in (
            ('HTTP_TIMEOUT_SECONDS', self.http_timeout_seconds, .2, 15),
            ('PAIR_TIMEOUT_SECONDS', self.pair_timeout_seconds, 1, 600),
            ('SCAN_REPEAT_SECONDS', self.scan_repeat_seconds, 0, 60),
            ('SCAN_MAX_AGE_SECONDS', self.scan_max_age_seconds, .1, 30),
            ('WORKER_STALL_SECONDS', self.worker_stall_seconds, 15, 120),
            ('SHUTDOWN_TIMEOUT_SECONDS', self.shutdown_timeout_seconds, 1, 20),
            ('STARTUP_CHECK_SECONDS', self.startup_check_seconds, 1, 15),
        ):
            if not math.isfinite(value) or not minimum <= value <= maximum:
                raise ValueError(f'{name} harus antara {minimum} dan {maximum}')
        if self.worker_stall_seconds < self.http_timeout_seconds * 2 + 5:
            raise ValueError('WORKER_STALL_SECONDS terlalu kecil untuk HTTP_TIMEOUT_SECONDS')
        if self.shutdown_timeout_seconds < self.http_timeout_seconds + 1:
            raise ValueError('SHUTDOWN_TIMEOUT_SECONDS harus >= HTTP_TIMEOUT_SECONDS + 1')
        if self.success_retention_days < 0:
            raise ValueError('SUCCESS_RETENTION_DAYS harus >=0')
        if not 1 <= self.camera_stream_fps <= 8:
            raise ValueError('CAMERA_STREAM_FPS harus antara 1 dan 8')
        used = {
            'switch cek': self.switch_check_gpio,
            'switch daftar': self.switch_register_gpio,
        }
        if self.rfid_enabled:
            used['RC522 RST'] = self.rfid_rst_gpio
            # SPI0 bus pins: 9, 10, 11 and CS0=8 or CS1=7 (cannot be used for GPIO)
            if self.rfid_spi_device not in (0, 1):
                raise ValueError('RFID_SPI_DEVICE hanya 0 (CE0) atau 1 (CE1)')
        if self.led_enabled:
            used.update({'LED R':self.led_red_gpio,'LED G':self.led_green_gpio,'LED B':self.led_blue_gpio})
        if self.buzzer_enabled:
            used['buzzer'] = self.buzzer_gpio
        if len(set(used.values())) != len(used):
            raise ValueError('Konflik GPIO: dua komponen memakai pin BCM yang sama')
        reserved = {2,3}
        if self.rfid_enabled:
            reserved |= {7 if self.rfid_spi_device == 1 else 8, 9,10,11}
        clashes = reserved.intersection(used.values())
        if clashes:
            raise ValueError(f'GPIO {sorted(clashes)} terpakai oleh SPI/I2C: ubah alokasi pin')
        for name, bcm in used.items():
            if bcm < 2 or bcm > 27:
                raise ValueError(f'GPIO {bcm} pada {name} tidak tersedia di header utama')
