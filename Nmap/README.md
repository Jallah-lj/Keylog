# Nmap Scanner Project

This project is a simple Python-based Nmap scanner. It uses the `python-nmap` library to perform network scans and provides a simple command-line interface to scan hosts and ports.

## Features

*   Scan a single host or a range of hosts.
*   Specify ports to scan.
*   Get information about open ports, services, and OS.

## Installation

1.  Clone the repository:
    ```bash
    git clone https://github.com/Jallah-lj/Nmap.git
    ```
2.  Navigate to the project directory:
    ```bash
    cd Nmap
    ```
3.  **Prerequisites**: Ensure Nmap is installed on your system:
    ```bash
    sudo apt install nmap
    ```

4.  **Set up a Virtual Environment**:
    It is recommended (and often required) to use a virtual environment:
    ```bash
    python3 -m venv venv
    source venv/bin/activate
    ```

5.  Install the dependencies:
    ```bash
    pip install -r requirements.txt
    ```

## Usage

```bash
# Verify venv is active (you should see (venv) in prompt)
python scanner.py <host> [ports]
```

*   `<host>`: The target host to scan (e.g., `127.0.0.1`, `scanme.nmap.org`).
*   `[ports]`: (Optional) The ports to scan (e.g., `22,80,443`). If not provided, a default set of ports will be scanned.

## Example

```bash
python scanner.py scanme.nmap.org 22,80
```
