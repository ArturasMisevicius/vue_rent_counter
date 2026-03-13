# Mobile Debugging

## General approach
- Reproduce on a real device first.
- Separate JS/UI issues from native issues.
- Capture logs, network traces, and device state.

## iOS
- Use Xcode console and Instruments.
- Check crash logs and symbolication.

## Android
- Use Logcat and Android Studio profiler.
- Watch for ANR and strict mode warnings.

## Cross-platform tooling
- Use Flipper or platform-specific inspectors when available.
- Profile startup time, frame drops, and memory usage.
