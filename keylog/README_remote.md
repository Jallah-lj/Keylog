# Windows Raw Input Keylogger (Educational)

> ⚠️ **FOR AUTHORIZED CYBERSECURITY RESEARCH ONLY**  
> **Run exclusively in air-gapped, isolated virtual machines. Never on real systems.**

![C++](https://img.shields.io/badge/language-C%2B%2B-00599C.svg)
![Windows](https://img.shields.io/badge/OS-Windows-blue)
![License](https://img.shields.io/badge/license-MIT-green)

This repository demonstrates a **low-level keyboard input monitor** for **educational malware analysis**, **defensive security research**, and **ethical hacking training**. It captures keystrokes using the Windows **Raw Input API** (`WM_INPUT`) and logs them to a local file—**with no network capability**.

Built to help security professionals understand how real-world keyloggers operate at the OS level, so they can better detect, analyze, and defend against them.

---

## ⚠️ Legal & Ethical Notice

-  **This tool is for educational and research purposes ONLY.**
- **Must be used exclusively in controlled, air-gapped virtual machines** (e.g., Windows 10 VM with no internet access).
-  **Never install or run on any system you do not fully own and control.**
- **Unauthorized use may violate computer fraud laws (e.g., CFAA) and result in criminal liability.**

> By using this code, you acknowledge that you are conducting **authorized, ethical research** in compliance with all applicable laws and institutional policies.

---

##  Purpose & Use Cases

-  Analyze **how keyloggers capture global input** at the Windows API level  
-  Develop **detection rules** for EDR/XDR/SIEM systems  
-   Train in **malware reverse engineering** and **behavioral analysis**  
-  Understand **Raw Input**, window messaging, and stealth techniques  
- Build **sandboxed labs** for defensive security validation  

This project **does not**:
- Connect to the internet
- Exfiltrate data
- Bypass antivirus (intentionally)
- Target specific applications

---

##  Technical Overview

- **Language**: C++ (Win32 API)
- **Input Method**: `RegisterRawInputDevices` + `WM_INPUT`
- **Stealth**: Hidden window (`SW_HIDE`), no tray icon, no console
- **Logging**: Appends to `C:\keylog_analysis.txt` (local only)
- **Character Handling**: Basic printable ASCII (can be extended with `ToUnicode`)

>  **Note**: This is a simplified implementation. Real-world malware uses more advanced techniques (e.g., DLL injection, ring-0 drivers, encrypted C2). This version is intentionally limited for safe study.

---

##  Build Instructions (VM Only!)

> **Prerequisites**: Windows VM (e.g., Windows 10) + MinGW/G++ or Visual Studio

### Using MinGW (e.g., via MSYS2 or TDM-GCC):
```bash
g++ -o keylog.exe keylog.cpp -lgdi32 -luser32
