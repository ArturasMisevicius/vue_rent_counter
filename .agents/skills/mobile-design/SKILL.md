---
name: mobile-design
description: Mobile-first design thinking and decision-making for iOS and Android apps. Use when designing mobile UX, screen flows, navigation patterns, touch interactions, performance constraints, offline behavior, or platform-specific conventions for React Native, Flutter, SwiftUI, or Android Compose.
---

# Mobile Design

## Overview
Design for touch, interruptions, and constrained resources. Mobile is not a small desktop. Prioritize platform-respectful patterns, offline resilience, and performance-aware UI decisions.

## Tooling
Allowed tools: Read, Glob, Grep, Bash.

## Mandatory Questions (ask before assuming)
- Platform: iOS, Android, or both?
- Framework: React Native, Flutter, or native (SwiftUI / Compose)?
- Navigation: tab bar, drawer, or stack?
- State management: Zustand / Redux / Riverpod / BLoC or existing app standard?
- Offline: must it work offline?
- Target devices: phone only, or tablet too?

If any answer is missing, ask the user before proceeding.

## Mandatory Reading
Read these reference files before any mobile work.

Universal (always read):
- references/mobile-design-thinking.md
- references/touch-psychology.md
- references/mobile-performance.md
- references/mobile-backend.md
- references/mobile-testing.md
- references/mobile-debugging.md

Read when relevant:
- references/mobile-navigation.md
- references/mobile-typography.md
- references/mobile-color-system.md
- references/decision-trees.md

Platform-specific:
- If iOS: references/platform-ios.md (read first)
- If Android: references/platform-android.md (read first)
- If cross-platform: read both platform files and apply conditional platform logic

## Runtime Scripts
Run the audit script for a quick UX and touch review.

- `python scripts/mobile_audit.py <project_path>`

Do not read the script unless you need to patch or extend it.

## Core Principles
- Touch-first: targets are at least 44pt (iOS) / 48dp (Android) with 8-12px spacing.
- Thumb zone: place primary actions in easy reach.
- Platform-respectful: iOS feels iOS, Android feels Android.
- Offline-capable: design error and recovery states for weak networks.
- Battery-conscious: avoid heavy background work and needless animations.

## Mandatory Checkpoint (complete before writing any mobile code)
```
CHECKPOINT:

Platform:   [ iOS / Android / Both ]
Framework:  [ React Native / Flutter / SwiftUI / Kotlin ]
Files Read: [ list the reference files you opened ]

3 Principles I Will Apply:
1. _______________
2. _______________
3. _______________

Anti-Patterns I Will Avoid:
1. _______________
2. _______________
```

## Anti-Patterns (do not do)
Performance:
- Do not use ScrollView for long lists; use FlatList / FlashList / ListView.builder.
- Do not use inline renderItem; memoize and use useCallback.
- Do not use index keys; use stable IDs.
- Do not set useNativeDriver: false for animations.

Touch and UX:
- Do not create touch targets smaller than 44-48px.
- Do not rely on gesture-only actions; provide buttons.
- Do not omit loading or error states.
- Do not ignore offline behavior; provide cached or degraded states.

Security:
- Do not store tokens in AsyncStorage or SharedPreferences; use SecureStore / Keychain / EncryptedSharedPreferences.
- Do not hardcode API keys or log sensitive data.

Architecture:
- Do not put business logic in UI; use services.
- Do not use global state by default; keep it local and lift when necessary.
- Do not skip cleanup of subscriptions or timers.
- Plan deep linking from day one.

## Pre-Development Checklist
Before starting any mobile project:
- Platform confirmed (iOS / Android / Both)
- Framework chosen (RN / Flutter / Native)
- Navigation pattern decided (Tabs / Stack / Drawer)
- State management selected
- Offline requirements known
- Deep linking planned
- Target devices defined (Phone / Tablet / Both)

Before every screen:
- Touch targets at least 44-48px
- Primary CTA in thumb zone
- Loading state exists
- Error state with retry exists
- Offline handling considered
- Platform conventions followed

Before release:
- Remove console.log or debug logs
- SecureStore for sensitive data
- SSL pinning enabled
- Lists optimized (memo, keyExtractor)
- Memory cleanup on unmount
- Tested on low-end devices
- Accessibility labels on interactive elements

## Reminder
Design for the worst conditions: bad network, one hand, bright sun, low battery. If it works there, it works everywhere.
