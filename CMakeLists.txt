# CMakeLists.txt for Keylogger Project

cmake_minimum_required(VERSION 3.0)
project(Keylogger)

set(CMAKE_CXX_STANDARD 14)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

# Specify the source files for the project
set(SOURCES
    # Add your source files here
    main.cpp
    keylogger.cpp
)

# Add executable
add_executable(Keylogger ${SOURCES})

# Link libraries
if(WIN32)
    target_link_libraries(Keylogger user32 gdi32 psapi winhttp)
endif()

# Specify the output directory
set(CMAKE_RUNTIME_OUTPUT_DIRECTORY ${CMAKE_BINARY_DIR}/bin)