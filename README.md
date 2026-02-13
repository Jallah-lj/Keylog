# Keylogger Implementations

This repository contains four different implementations of a keylogger for educational purposes.

## Implementations

1. **keylog.cpp**: Basic keylogging functionality.
2. **keylog_advanced.cpp**: Advanced features including stealth mode.
3. **keylog_online.cpp**: Keylogger that transmits data over the internet.
4. **keylog_hook.cpp**: Uses Windows hooks to capture keystrokes.

## Build Instructions

### Manual Compilation
To compile the keylogger from the source code:
1. Use your preferred C++ compiler (e.g., g++).
2. Run the following command:
   ```
   g++ keylog.cpp -o keylog
   ```

### Using Makefile
To build using a Makefile:
1. Execute:
   ```
   make
   ```

### Using CMake
To build using CMake:
1. Create a build directory:
   ```
   mkdir build && cd build
   ```
2. Run CMake:
   ```
   cmake ..
   make
   ```

## Technical Architecture

```
[User Input]
   |
   |--- [Capture Keystrokes]
   |
   |--- [Process Data]
   |
   |--- [Store/Transmit Data]
```

## Detection Methods

### YARA Rules
Create YARA rules to detect known keylogger signatures.

### PowerShell Scripts
Use the following PowerShell script to detect keylogger processes:
```powershell
Get-Process | Where-Object { $_.ProcessName -like "*keylog*" }
```

## Prevention Techniques

1. **For Users**:
   - Regularly update antivirus software.
   - Avoid downloading untrusted sources.

2. **For Administrators**:
   - Monitor system logs.
   - Implement strict access controls.

## Educational Use Cases

- Understanding keylogging techniques for cybersecurity training.
- Developing secure software by recognizing potential threats.

## Legal and Ethical Guidelines

Keyloggers can be used maliciously, and in many jurisdictions, their use is governed by laws such as:
- **CFAA (Computer Fraud and Abuse Act)**
- **GDPR** (General Data Protection Regulation)
- **Computer Misuse Act**

Always ensure compliance with local laws when using keyloggers.

## References

1. [CFAA Overview](https://www.law.cornell.edu/wex/computer_fraud_and_abuse_act)
2. [GDPR Guidelines](https://gdpr-info.eu/)
3. [Computer Misuse Act](https://www.legislation.gov.uk/ukpga/1990/18/contents)
