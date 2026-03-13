# Mobile Navigation

## Common patterns
- Tab bar: primary destinations, 3-5 max.
- Stack: drill-down flows, details, and wizards.
- Drawer: secondary navigation or dense admin apps.

## Platform differences
- iOS: edge swipe back; prefer bottom sheets for secondary actions.
- Android: system back button and back gesture must work.

## Deep linking
- Plan deep links from day one.
- Ensure push notifications open the correct screen state.
- Handle cold-start and logged-out flows gracefully.
