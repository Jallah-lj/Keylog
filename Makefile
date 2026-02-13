# Makefile

CXX = g++
CXXFLAGS = -Wall -Wextra -std=c++11

# Target for all keylogger implementations
all: keylog keylog_advanced keylog_online keylog_hook

keylog: keylog.cpp
	$(CXX) $(CXXFLAGS) -o keylog keylog.cpp

keylog_advanced: keylog_advanced.cpp
	$(CXX) $(CXXFLAGS) -o keylog_advanced keylog_advanced.cpp

keylog_online: keylog_online.cpp
	$(CXX) $(CXXFLAGS) -o keylog_online keylog_online.cpp

keylog_hook: keylog_hook.cpp
	$(CXX) $(CXXFLAGS) -o keylog_hook keylog_hook.cpp

# Clean target to remove generated files
clean:
	rm -f keylog keylog_advanced keylog_online keylog_hook