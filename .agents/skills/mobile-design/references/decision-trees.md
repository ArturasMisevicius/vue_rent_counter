# Decision Trees

## Framework decision tree
```
WHAT ARE YOU BUILDING?
    |
    |-- Need OTA updates + rapid iteration + web team
    |   -> React Native + Expo
    |
    |-- Need pixel-perfect custom UI + performance critical
    |   -> Flutter
    |
    |-- Deep native features + single platform focus
    |   |-- iOS only -> SwiftUI
    |   |-- Android only -> Kotlin + Jetpack Compose
    |
    |-- Existing RN codebase + new features
    |   -> React Native (bare workflow)
    |
    |-- Enterprise + existing Flutter codebase
        -> Flutter
```

## When to unify vs diverge
Unify (same on both):
- Business logic
- Data layer
- Core features

Diverge (platform-specific):
- Navigation behavior
- Gestures and back handling
- Icons
- Date pickers
- Modals and sheets
- Typography
- Error dialogs

## Quick reference: platform defaults
| Element | iOS | Android |
| --- | --- | --- |
| Primary font | SF Pro / SF Compact | Roboto |
| Min touch target | 44pt x 44pt | 48dp x 48dp |
| Back navigation | Edge swipe left | System back button/gesture |
| Bottom tab icons | SF Symbols | Material Symbols |
| Action sheet | Bottom sheet | Bottom sheet / dialog |
| Progress | Spinner | Linear progress |
| Pull to refresh | UIRefreshControl | SwipeRefreshLayout |
