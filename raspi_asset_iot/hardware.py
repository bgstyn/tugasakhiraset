"""Raspberry Pi 5 hardware: SPI RC522, USB webcam, 16x2 I2C and GPIOZero.

All GPIO numbers are BCM. Requires Raspberry Pi OS Trixie and apt packages.
"""
import logging
import queue
import threading
import time

LOG = logging.getLogger(__name__)


def emit(events, stop, event):
    while not stop.is_set():
        try:
            events.put(event, timeout=0.2)
            return
        except queue.Full:
            continue


class RC522:
    """Minimal MFRC522 UID-only reader. SPI0 CE0 + optional RST output.

    No dependency on legacy RPi.GPIO. Supports 4/7/10 byte ISO14443A UIDs,
    and deliberately does not write to cards or authenticate sectors.
    """
    # MFRC522 register addresses
    COMMAND = 0x01
    COM_IRQ = 0x04
    DIV_IRQ = 0x05
    ERROR = 0x06
    FIFO_DATA = 0x09
    FIFO_LEVEL = 0x0A
    CONTROL = 0x0C
    BIT_FRAMING = 0x0D
    MODE = 0x11
    TX_CONTROL = 0x14
    TX_ASK = 0x15
    CRC_H = 0x21
    CRC_L = 0x22
    T_MODE = 0x2A
    T_PRESCALER = 0x2B
    T_RELOAD_H = 0x2C
    T_RELOAD_L = 0x2D

    def __init__(self, pin_factory, rst_gpio=25, spi_device=0):
        import spidev
        from gpiozero import DigitalOutputDevice
        self.rst = DigitalOutputDevice(rst_gpio, active_high=True, initial_value=True, pin_factory=pin_factory)
        self.spi = spidev.SpiDev()
        try:
            self.spi.open(0, spi_device)
            self.spi.max_speed_hz = 1000000
            self.spi.mode = 0
            self._init_chip()
        except Exception:
            self.close()
            raise

    def _wr(self, reg, val):
        self.spi.xfer2([(reg << 1) & 0x7E, val & 0xFF])

    def _rd(self, reg):
        return self.spi.xfer2([((reg << 1) & 0x7E) | 0x80, 0])[1]

    def _set(self, reg, mask):
        self._wr(reg, self._rd(reg) | mask)

    def _clear(self, reg, mask):
        self._wr(reg, self._rd(reg) & ~mask)

    def _init_chip(self):
        self.rst.on()
        time.sleep(0.05)
        self._wr(self.COMMAND, 0x0F)
        time.sleep(0.05)
        self._wr(self.T_MODE, 0x8D)
        self._wr(self.T_PRESCALER, 0x3E)
        self._wr(self.T_RELOAD_L, 30)
        self._wr(self.T_RELOAD_H, 0)
        self._wr(self.TX_ASK, 0x40)
        self._wr(self.MODE, 0x3D)
        if (self._rd(self.TX_CONTROL) & 0x03) != 0x03:
            self._set(self.TX_CONTROL, 0x03)

    def _exchange(self, data: list, tx_last_bits: int = 0):
        self._wr(self.COMMAND, 0x00)
        self._wr(self.COM_IRQ, 0x7F)
        self._wr(self.FIFO_LEVEL, 0x80)
        for byte in data:
            self._wr(self.FIFO_DATA, byte)
        self._wr(self.BIT_FRAMING, tx_last_bits & 0x07)
        self._wr(self.COMMAND, 0x0C)
        self._set(self.BIT_FRAMING, 0x80)
        deadline = time.monotonic() + 0.05
        ok = False
        while time.monotonic() < deadline:
            irq = self._rd(self.COM_IRQ)
            if irq & 0x30:
                ok = True
                break
            if irq & 0x01:
                break
            time.sleep(0.0002)
        self._clear(self.BIT_FRAMING, 0x80)
        if not ok or self._rd(self.ERROR) & 0x1B:
            return None, 0
        count = min(self._rd(self.FIFO_LEVEL), 64)
        received = [self._rd(self.FIFO_DATA) for _ in range(count)]
        last_bits = self._rd(self.CONTROL) & 0x07
        nbits = (count - 1) * 8 + last_bits if last_bits else count * 8
        return received, nbits

    def _crc(self, data: list):
        self._wr(self.COMMAND, 0x00)
        self._wr(self.DIV_IRQ, 0x04)
        self._wr(self.FIFO_LEVEL, 0x80)
        for byte in data:
            self._wr(self.FIFO_DATA, byte)
        self._wr(self.COMMAND, 0x03)
        deadline = time.monotonic() + 0.05
        while time.monotonic() < deadline:
            if self._rd(self.DIV_IRQ) & 0x04:
                return [self._rd(self.CRC_L), self._rd(self.CRC_H)]
            time.sleep(0.0002)
        return None

    def read_uid(self):
        # 7-bit REQA avoids continually waking a halted tag; no HALT required
        response, nbits = self._exchange([0x26], tx_last_bits=7)
        if not response or nbits != 16:
            return None
        uid = []
        for cascade in (0x93, 0x95, 0x97):
            block, nbits = self._exchange([cascade, 0x20])
            if not block or nbits != 40 or len(block) < 5:
                return None
            if block[0] ^ block[1] ^ block[2] ^ block[3] != block[4]:
                return None
            crc = self._crc([cascade, 0x70] + block[:5])
            if crc is None:
                return None
            selection, nbits = self._exchange([cascade, 0x70] + block[:5] + crc)
            if not selection or nbits < 24:
                return None
            if block[0] == 0x88:
                uid.extend(block[1:4])
            else:
                uid.extend(block[:4])
            if not (selection[0] & 0x04):
                return bytes(uid)
        return None

    def close(self):
        try:
            self.spi.close()
        finally:
            self.rst.close()


def uid_as_string(uid: bytes, fmt: str):
    if fmt == 'decimal':
        return str(int.from_bytes(uid, byteorder='big'))
    return uid.hex().upper()


class Lcd1602:
    """Common PCF8574 backpack wiring: P0=RS, P1=RW, P2=E, P3=BL, P4..7=DATA.
    Use a bidirectional I2C level shifter when powering LCD at 5 V.
    """
    def __init__(self, address: int):
        import smbus
        self.bus = smbus.SMBus(1)
        self.address = address
        self.lock = threading.Lock()
        try:
            time.sleep(0.06)
            for cmd in (0x30, 0x30, 0x30, 0x20):
                self._nibble(cmd, 0)
                time.sleep(0.005)
            for cmd in (0x28, 0x08, 0x01, 0x06, 0x0C):
                self._command(cmd)
            self.show('RASPI ASSET IOT', 'MEMULAI...')
        except Exception:
            self.bus.close()
            raise

    def _port(self, value):
        self.bus.write_byte(self.address, value)

    def _nibble(self, bits, rs):
        data = (bits & 0xF0) | 0x08 | (rs & 0x01)
        self._port(data | 0x04)
        time.sleep(0.0001)
        self._port(data)
        time.sleep(0.00006)

    def _byte(self, value, rs):
        self._nibble(value & 0xF0, rs)
        self._nibble((value << 4) & 0xF0, rs)

    def _command(self, cmd):
        self._byte(cmd, 0)
        if cmd in (0x01, 0x02):
            time.sleep(0.002)

    def show(self, line1, line2):
        with self.lock:
            for pos, text in ((0x80, line1), (0xC0, line2)):
                self._command(pos)
                clean = str(text).encode('ascii', 'replace').decode('ascii')[:16].ljust(16)
                for c in clean:
                    self._byte(ord(c), 1)

    def close(self):
        try:
            self.show('', '')
        finally:
            self.bus.close()


class Indicator:
    def __init__(self, pin_factory, config):
        from gpiozero import DigitalOutputDevice
        active_high = config.led_type == 'common_cathode'
        self.led = {}
        self.buzzer = None
        self.lcd = None
        self.last_lines = None
        self.beeps = queue.Queue(maxsize=15)
        self.stop = threading.Event()
        self.beep_thread = threading.Thread(target=self._beeper, daemon=True)
        try:
            if config.led_enabled:
                for name, gpio in (('red', config.led_red_gpio), ('green', config.led_green_gpio), ('blue', config.led_blue_gpio)):
                    self.led[name] = DigitalOutputDevice(gpio, active_high=active_high, initial_value=False, pin_factory=pin_factory)
            if config.buzzer_enabled:
                self.buzzer = DigitalOutputDevice(config.buzzer_gpio, active_high=True, initial_value=False, pin_factory=pin_factory)
                self.beep_thread.start()
        except Exception:
            self.close()
            raise
        if config.lcd_enabled:
            try:
                self.lcd = Lcd1602(config.lcd_address)
            except (ImportError, OSError):
                LOG.exception('LCD tidak tersedia; scanner tetap berjalan')

    def display(self, line1, line2):
        current = (line1[:16], line2[:16])
        if current == self.last_lines:
            return
        self.last_lines = current
        LOG.info('LCD: %-16s | %s', current[0], current[1])
        if self.lcd:
            try:
                self.lcd.show(*current)
            except Exception:
                self.last_lines = None
                LOG.exception('LCD gagal (cek alamat, kabel, level shifter)')

    def color(self, name):
        selected = {'success': 'green', 'failure': 'red', 'offline': 'blue'}.get(name)
        for channel, led in self.led.items():
            if channel != selected:
                led.off()
        if selected in self.led:
            self.led[selected].on()

    def beep(self, times):
        if not self.buzzer:
            return
        try:
            self.beeps.put_nowait(times)
        except queue.Full:
            pass

    def _beeper(self):
        while not self.stop.is_set():
            try:
                n = self.beeps.get(timeout=0.2)
            except queue.Empty:
                continue
            for i in range(n):
                if self.stop.is_set():
                    break
                self.buzzer.on()
                try:
                    self.stop.wait(0.12)
                finally:
                    self.buzzer.off()
                if i + 1 < n and self.stop.wait(0.14):
                    break

    def close(self):
        self.stop.set()
        for led in self.led.values():
            try:
                led.off()
            except Exception:
                LOG.exception('Gagal mematikan kanal LED')
        if self.beep_thread.ident is not None:
            self.beep_thread.join()
        for device in [*self.led.values(), self.buzzer, self.lcd]:
            if device is None:
                continue
            try:
                if device is self.buzzer:
                    device.off()
                device.close()
            except Exception:
                LOG.exception('Gagal menutup perangkat indikator')


class ModeSwitch:
    """SPDT: GPIO17/27 to outside contacts, middle COM to GND."""
    def __init__(self, pin_factory, check_gpio=17, register_gpio=27):
        from gpiozero import Button
        self.left = Button(check_gpio, pull_up=True, bounce_time=0.05, pin_factory=pin_factory)
        try:
            self.right = Button(register_gpio, pull_up=True, bounce_time=0.05, pin_factory=pin_factory)
        except Exception:
            self.left.close()
            raise

    def read(self):
        l, r = self.left.is_pressed, self.right.is_pressed
        if l and not r:
            return 'check'
        if r and not l:
            return 'register'
        return 'invalid'  # wiring problem or a 3-position center-off switch

    def close(self):
        self.left.close()
        self.right.close()


class QrDecoder:
    def __init__(self, cv2):
        self.opencv = cv2.QRCodeDetector()
        try:
            from pyzbar import pyzbar
            self.zbar = pyzbar
        except (ImportError, OSError):
            self.zbar = None

    def decode(self, frame):
        if self.zbar:
            for symbol in self.zbar.decode(frame, symbols=[self.zbar.ZBarSymbol.QRCODE]):
                try:
                    text = symbol.data.decode('utf-8').strip()
                except UnicodeDecodeError:
                    continue
                if text:
                    return text, None

        text, corners, _ = self.opencv.detectAndDecode(frame)
        return text, corners


class WebcamWorker(threading.Thread):
    def __init__(self, events, stop, camera_index=0, publish_frame=None, *, stream_fps=5):
        super().__init__(daemon=True, name='webcam')
        self.events, self.stop, self.index = events, stop, camera_index
        self.publish_frame = publish_frame
        self.frame_interval = 1 / stream_fps
        self.reset_detection = threading.Event()
        self.last_activity = None

    def _emit(self, event):
        emit(self.events, self.stop, event)

    def run(self):
        import cv2
        detector = QrDecoder(cv2)
        while not self.stop.is_set():
            cap = cv2.VideoCapture(self.index, cv2.CAP_V4L2)
            if not cap.isOpened():
                LOG.error('Webcam /dev/video%s tidak terbuka; ulangi 5 detik', self.index)
                cap.release()
                self._emit(('camera_error', None))
                self.stop.wait(5)
                continue
            cap.set(cv2.CAP_PROP_FRAME_WIDTH, 640)
            cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 480)
            cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
            last, missing_since, unreadable_since, last_bad_report = None, None, None, 0
            next_frame_at = next_scan_at = 0
            try:
                while not self.stop.is_set():
                    if self.reset_detection.is_set():
                        self.reset_detection.clear()
                        last = None
                    ok, frame = cap.read()
                    if not ok:
                        raise RuntimeError('Webcam gagal membaca frame')
                    now = time.monotonic()
                    self.last_activity = now
                    if self.publish_frame and now >= next_frame_at:
                        next_frame_at = now + self.frame_interval
                        height, width = frame.shape[:2]
                        preview = frame
                        if width > 480:
                            preview = cv2.resize(frame, (480, max(1, round(height * 480 / width))), interpolation=cv2.INTER_AREA)
                        encoded, jpeg = cv2.imencode('.jpg', preview, [cv2.IMWRITE_JPEG_QUALITY, 60])
                        if encoded:
                            self.publish_frame(jpeg.tobytes())
                    if now < next_scan_at:
                        continue
                    next_scan_at = now + 0.075
                    decoded, corners = detector.decode(frame)
                    if decoded:
                        missing_since = None
                        unreadable_since = None
                        decoded = decoded.strip()
                        if decoded and decoded != last:
                            last = decoded
                            self._emit(('qr', decoded))
                    else:
                        if corners is not None:
                            if unreadable_since is None:
                                unreadable_since = now
                            if now - unreadable_since >= 1.5 and now - last_bad_report >= 7:
                                self._emit(('scan_error', 'QR KURANG JELAS'))
                                last_bad_report = now
                        else:
                            unreadable_since = None
                        if missing_since is None:
                            missing_since = now
                        if now - missing_since >= 0.8:
                            last = None
            except Exception:
                LOG.exception('Kamera error; restart otomatis')
                self._emit(('camera_error', None))
            finally:
                self.last_activity = None
                cap.release()
            self.stop.wait(2)


class RfidWorker(threading.Thread):
    def __init__(self, events, stop, pin_factory, uid_format, rst_gpio=25, spi_device=0):
        super().__init__(daemon=True, name='rc522')
        self.events, self.stop = events, stop
        self.pin_factory, self.uid_format = pin_factory, uid_format
        self.rst_gpio, self.spi_device = rst_gpio, spi_device
        self.reset_detection = threading.Event()
        self.last_activity = None

    def _emit(self, event):
        emit(self.events, self.stop, event)

    def run(self):
        while not self.stop.is_set():
            reader = None
            try:
                reader = RC522(self.pin_factory, self.rst_gpio, self.spi_device)
                last, empty_since = None, None
                while not self.stop.is_set():
                    if self.reset_detection.is_set():
                        self.reset_detection.clear()
                        last = None
                    raw = reader.read_uid()
                    now = time.monotonic()
                    self.last_activity = now
                    if raw:
                        empty_since = None
                        uid = uid_as_string(raw, self.uid_format)
                        if uid != last:
                            last = uid
                            self._emit(('rfid', uid))
                    else:
                        if empty_since is None:
                            empty_since = now
                        if now - empty_since > 0.6:
                            last = None
                    self.stop.wait(0.1)
            except Exception:
                LOG.exception('RC522 error; coba ulang setelah 5 detik')
                self._emit(('rfid_error', None))
            finally:
                self.last_activity = None
                if reader:
                    reader.close()
            self.stop.wait(5)
