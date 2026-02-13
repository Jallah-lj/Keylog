cmake_minimum_required(VERSION 3.10)

# Set the project name
project(Keylog)

# Specify the C++ standard
set(CMAKE_CXX_STANDARD 11)
set(CMAKE_CXX_STANDARD_REQUIRED True)

# Add the executable for each keylogger implementation
# Assuming there are implementations in separate directories
add_subdirectory(impl1)
add_subdirectory(impl2)
add_subdirectory(impl3)

# Find additional libraries if needed
find_package(SomeRequiredLibrary REQUIRED)

# Include directories
include_directories(include)

# Set the output directory for the compiled binaries
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/bin)