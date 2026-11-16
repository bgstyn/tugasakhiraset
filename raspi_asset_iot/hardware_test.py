#!/usr/bin/env python3
"""Manual tests run WITHOUT contacting Laravel. Always verify wiring first."""
import argparse
import time
from dataclasses import replace
from config import Config


def main():
    parser=argparse.ArgumentParser(description='Manual Pi 5 hardware tests (offline)')
    parser.add_argument('component',choices=['switch','lcd','led','buzzer','rfid','camera'])
    args=parser.parse_args()
    c=Config()
    if args.component in ('switch','led','buzzer','rfid'):
        from gpiozero.pins.lgpio import LGPIOFactory
        factory=LGPIOFactory()
    else:
        factory=None
    try:
        if args.component == 'switch':
            from hardware import ModeSwitch
            device=ModeSwitch(factory, c.switch_check_gpio, c.switch_register_gpio)
            try:
                print('Gerakkan switch ke kiri dan kanan. Ctrl+C untuk selesai.')
                while True:
                    mode=device.read()
                    print('Mode:',mode,' ' * 16,end='\r',flush=True)
                    time.sleep(0.25)
            finally:
                device.close()
        elif args.component == 'lcd':
            if not c.lcd_enabled:
                raise SystemExit('LCD_ENABLED=false. Aktifkan hanya setelah memverifikasi level shifter I2C bila LCD 5 V.')
            from hardware import Lcd1602
            d=Lcd1602(c.lcd_address)
            try:
                d.show('LCD TERHUBUNG','TES 1234567890')
                time.sleep(3)
            finally:
                d.close()
        elif args.component == 'led':
            if not c.led_enabled:
                raise SystemExit('LED_ENABLED=false. Pastikan tipe common dan resistor sebelum dinyalakan.')
            from hardware import Indicator
            indicator = Indicator(factory, replace(c, lcd_enabled=False, buzzer_enabled=False))
            try:
                for status, name, pin in (
                    ('failure', 'MERAH', c.led_red_gpio),
                    ('success', 'HIJAU', c.led_green_gpio),
                    ('offline', 'BIRU', c.led_blue_gpio),
                ):
                    print(f'BCM {pin}: seharusnya hanya {name}')
                    indicator.color(status)
                    time.sleep(2)
                    indicator.color(None)
                    time.sleep(0.5)
            finally:
                indicator.close()
        elif args.component == 'buzzer':
            if not c.buzzer_enabled:
                raise SystemExit('BUZZER_ENABLED=false. Pastikan tegangan buzzer dan driver transistor sebelum mengaktifkan.')
            from gpiozero import DigitalOutputDevice
            buz=DigitalOutputDevice(c.buzzer_gpio,active_high=True,initial_value=False,pin_factory=factory)
            try:
                for n in (1,2):
                    print('Beep:',n)
                    for i in range(n):
                        buz.on(); time.sleep(0.12); buz.off(); time.sleep(0.13)
                    time.sleep(0.7)
            finally:
                buz.off()
                buz.close()
        elif args.component == 'rfid':
            from hardware import RC522,uid_as_string
            d=RC522(factory,c.rfid_rst_gpio,c.rfid_spi_device)
            try:
                print('Tempelkan kartu RFID ke RC522; Ctrl+C selesai')
                last=''
                while True:
                    raw=d.read_uid()
                    if raw:
                        code=uid_as_string(raw,c.rfid_uid_format)
                        if code!=last:
                            print('UID:',code)
                        last=code
                    else:
                        last=''
                    time.sleep(0.15)
            finally:
                d.close()
        elif args.component == 'camera':
            import cv2
            camera=cv2.VideoCapture(c.camera_index,cv2.CAP_V4L2)
            if not camera.isOpened():
                raise RuntimeError('Webcam tidak terbuka; cek /dev/video0 dan grup video')
            detector=cv2.QRCodeDetector()
            try:
                print('Arahkan QR Code; Ctrl+C selesai')
                seen=set()
                while True:
                    ok,frame=camera.read()
                    if not ok:
                        raise RuntimeError('Webcam gagal membaca gambar')
                    decoded,_,_=detector.detectAndDecode(frame)
                    if decoded and decoded not in seen:
                        seen.add(decoded)
                        print('QR:',decoded)
                    time.sleep(0.07)
            finally:
                camera.release()
    except KeyboardInterrupt:
        print('\nSelesai')
    finally:
        if factory:
            factory.close()

if __name__=='__main__':
    main()
