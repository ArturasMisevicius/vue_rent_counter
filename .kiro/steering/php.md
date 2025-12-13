---
inclusion: always
---



# ILO CODE – PHP 8.4 Strict Rules
- `declare(strict_types=1);` in EVERY file
- Never use `mixed`, `object`, or `array` types unless 100% unavoidable
- All properties MUST be typed + readonly when possible
- All class constructors use promoted readonly properties
- No public properties EVER
- All methods return types declared (never void if you can return self or value object)
- Use backed enums everywhere (string|int) + methods
- Use #[AllowDynamicProperties] NEVER
- Final classes by default unless designed for extension

# Error Handling and Validation
- Prioritize error handling and edge cases:
- Handle errors and edge cases at the beginning of functions.
- Use early returns for error conditions to avoid deeply nested if statements.
- Place the happy path last in the function for improved readability.
- Avoid unnecessary else statements; use if-return pattern instead.
- Use guard clauses to handle preconditions and invalid states early.
- Implement proper error logging and user-friendly error messages.
- Consider using custom error types or error factories for consistent error handling.


  Technical preferences:
  
  - Always use kebab-case for component names (e.g. my-component.blade.php)
  - Minimize the usage of client-side components to small, isolated Alpine.js components
  - Always add loading and error states to data fetching components
  - Implement error handling and error logging
  - Use semantic HTML elements where possible
  - Utilize Blade components for reusable UI elements
  
  General preferences:
  
  - Follow the user's requirements carefully & to the letter
  - Always write correct, up-to-date, bug-free, fully functional and working, secure, performant and efficient code
  - Focus on readability over being performant
  - Fully implement all requested functionality
  - Leave NO todos, placeholders or missing pieces in the code
  - Be sure to reference file names
  - Be concise. Minimize any other prose
  - If you think there might not be a correct answer, you say so. If you do not know the answer, say so instead of guessing
  