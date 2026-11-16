import sys
import threading
import time
import unittest
from types import SimpleNamespace
from unittest.mock import Mock, patch

from app import Controller
from core import Result
from hardware import Indicator


class FakeOutput:
    def __init__(self, pin, active_high, initial_value, pin_factory):
        self.pin = pin
        self.active_high = active_high
        self.level = active_high if initial_value else not active_high
        self.closed = False
        self.transitions = []
        self.changed = threading.Condition()

    def on(self):
        self.level = self.active_high
        with self.changed:
            self.transitions.append(True)
            self.changed.notify_all()

    def off(self):
        self.level = not self.active_high
        with self.changed:
            self.transitions.append(False)
            self.changed.notify_all()

    def close(self):
        self.closed = True

    def wait_for_transitions(self, count):
        with self.changed:
            return self.changed.wait_for(lambda: len(self.transitions) >= count, timeout=2)


class TestIndicator(unittest.TestCase):
    def setUp(self):
        config = SimpleNamespace(
            led_enabled=True, led_type='common_anode', led_red_gpio=13,
            led_green_gpio=5, led_blue_gpio=6, buzzer_enabled=True,
            buzzer_gpio=18, lcd_enabled=False,
        )
        gpiozero = SimpleNamespace(DigitalOutputDevice=FakeOutput)
        with patch.dict(sys.modules, {'gpiozero': gpiozero}):
            self.indicator = Indicator(None, config)
        self.addCleanup(self.indicator.close)

    def test_configured_common_anode_channels_are_active_low_and_exclusive(self):
        leds = self.indicator.led
        self.assertEqual([led.pin for led in leds.values()], [13, 5, 6])
        for status, expected in [
            (None, []), ('success', ['green']), ('failure', ['red']),
            ('offline', ['blue']), (None, []),
        ]:
            self.indicator.color(status)
            self.assertEqual([name for name, output in leds.items() if not output.level], expected)

    def test_beeps_once_then_twice_on_gpio18(self):
        buzzer = self.indicator.buzzer
        self.assertEqual(buzzer.pin, 18)
        self.indicator.beep(1)
        self.assertTrue(buzzer.wait_for_transitions(2))
        self.indicator.beep(2)
        self.assertTrue(buzzer.wait_for_transitions(6))
        self.assertEqual(buzzer.transitions, [True, False] * 3)

    def test_shutdown_turns_off_outputs_during_beep(self):
        buzzer = self.indicator.buzzer
        self.indicator.color('success')
        self.indicator.beep(2)
        self.assertTrue(buzzer.wait_for_transitions(1))
        self.indicator.close()
        self.assertTrue(all(led.level and led.closed for led in self.indicator.led.values()))
        self.assertFalse(buzzer.level)
        self.assertTrue(buzzer.closed)

    def test_scan_status_uses_correct_feedback_after_api_result(self):
        controller = Controller.__new__(Controller)
        controller.show = Mock()
        controller.outbox = Mock()
        event = {'id': 'event', 'kind': 'check'}
        for state, color, beeps in [('confirmed', 'success', 1), ('rejected', 'failure', 2), ('retry', 'offline', 2)]:
            with self.subTest(state=state):
                controller.latest_event_times = {'event': time.monotonic()}
                controller.on_result(event, Result(state, 'TITLE', 'DETAIL'))
                self.assertEqual(controller.show.call_args.args[2], color)
                self.assertEqual(controller.show.call_args.kwargs['beeps'], beeps)
                self.assertEqual(controller.latest_event_times, {})
