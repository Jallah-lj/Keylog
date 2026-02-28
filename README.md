# Keylogger Implementations (Python)

This repository contains four advanced Python implementations of a keylogger for **educational and cybersecurity research purposes only**.

## Implementations

| File | Description |
|------|-------------|
| `keylog.py` | Basic keylogging — appends every keystroke to `keylog.txt`. |
| `keylog_advanced.py` | Advanced keylogging with timestamps, active process name, and window title. |
| `keylog_hook.py` | Hook-based keylogger with human-readable labels for special keys (key-down & key-up events). |
| `keylog_online.py` | Buffers keystrokes and transmits them (Base64-encoded) to a remote server via HTTP POST. |

## Technical Architecture

```
[User Input]
      │
      ▼
[pynput Keyboard Listener]
      │
      ├──► [keylog.py]          → Appends raw keystrokes to keylog.txt
      │
      ├──► [keylog_advanced.py] → Logs timestamp + active process + window title + key
      │
      ├──► [keylog_hook.py]     → Maps special keys to human-readable labels, logs to file
      │
      └──► [keylog_online.py]   → Buffers keystrokes, Base64-encodes, POSTs to remote server
```

## Prerequisites

- Python 3.10 or newer

## Install Dependencies

```bash
pip install -r requirements.txt
```

## Run

```bash
# Basic keylogger
python keylog.py

# Advanced keylogger (timestamps + process/window info)
python keylog_advanced.py

# Hook-based keylogger (human-readable special-key labels)
python keylog_hook.py

# Online keylogger — set KEYLOG_SERVER_URL env var or edit SERVER_URL in keylog_online.py
python keylog_online.py
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `KEYLOG_FILE` | `keylog.txt` | Path to the local log file (used by `keylog.py`, `keylog_advanced.py`, `keylog_hook.py`). |
| `KEYLOG_SERVER_URL` | `https://yourserver.com/upload` | Endpoint for `keylog_online.py` to POST captured data. |
| `KEYLOG_BUFFER_SIZE` | `1024` | Number of keystrokes to buffer before transmitting in `keylog_online.py`. |

## Feature Comparison

| Feature | `keylog.py` | `keylog_advanced.py` | `keylog_hook.py` | `keylog_online.py` |
|---------|:-----------:|:--------------------:|:----------------:|:-----------------:|
| Keystroke capture | ✓ | ✓ | ✓ | ✓ |
| Timestamps | — | ✓ | — | — |
| Active process name | — | ✓ | — | — |
| Active window title | — | ✓ | — | — |
| Readable special-key labels | — | — | ✓ | — |
| Remote transmission | — | — | — | ✓ |
| Local file logging | ✓ | ✓ | ✓ | — |

## Detection Methods

### Process Inspection
```bash
ps aux | grep keylog
```

### YARA Rules
Create YARA rules targeting `pynput` imports or known keylogger signatures to detect these scripts at rest.

### Network Monitoring
Monitor outbound HTTP POST traffic to detect `keylog_online.py` transmissions.

## Prevention Techniques

1. **For Users**:
   - Keep antivirus/EDR software up to date.
   - Avoid executing untrusted Python scripts or binaries.
   - Use a hardware security key for authentication where possible.

2. **For Administrators**:
   - Monitor system and audit logs for unexpected processes.
   - Implement application allowlisting.
   - Restrict access to sensitive systems with least-privilege principles.

## Educational Use Cases

- Understanding keylogging techniques for cybersecurity training.
- Developing secure software by recognizing potential input-capture threats.
- Practicing incident response against credential-harvesting malware.

## Legal and Ethical Guidelines

> **⚠️ WARNING**: Deploying a keylogger on any system without explicit written permission from the owner is **illegal** and **unethical**.

These implementations are provided solely for:
- Academic research and coursework
- Authorized penetration testing
- Security awareness training on systems you own or have explicit permission to test

Relevant legislation includes:
- **CFAA** (Computer Fraud and Abuse Act) — United States
- **GDPR** (General Data Protection Regulation) — European Union
- **Computer Misuse Act 1990** — United Kingdom

## References

1. [CFAA Overview](https://www.law.cornell.edu/wex/computer_fraud_and_abuse_act)
2. [GDPR Guidelines](https://gdpr-info.eu/)
3. [Computer Misuse Act](https://www.legislation.gov.uk/ukpga/1990/18/contents)
4. [pynput documentation](https://pynput.readthedocs.io/)
