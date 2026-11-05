# Contributor portal design

The supplied mobile task-app reference guides the composition: a personal
greeting, large light-weight heading, rounded summary card, circular progress,
pill filters, white session cards and a floating bottom navigation. Desktop
uses the same components in two columns with a persistent sidebar.

## Visual decisions

- Canvas: white with pale green radial gradients (`#daf2d8`, `#e2f4e8`).
- Primary action / summary: forest green `#174c39`; highlighted action: mint
  `#d8efb8`. Primary text `#172c25`, secondary text `#64746c`.
- [Manrope](https://fonts.google.com/specimen/Manrope), self-hosted Latin variable
  WOFF2 (24.5 kB), with its SIL Open Font License in `public/fonts/Manrope-OFL.txt`.
  The screenshot does not establish an exact typeface; Manrope was selected for
  similar geometric shapes and proportions. Font display swap and preload avoid
  waiting on an external font provider.
- Card radius 24–30 px, pill navigation/actions, restrained shadows, thin SVG
  icons. Main heading 34 px mobile and up to 48 px desktop, weight 450.
- Mobile dock contains the existing five destinations. Active routes have
  aria-current; icon-only actions have labels. Form phone selectors are native
  buttons and duration inputs have accessible labels. Focus and reduced-motion
  styles are included. Numeric values use tabular figures.

## Implementation

`portal/layout.blade.php` includes the new `portal/shell.blade.php` and retains
the scanner, timer and alarm modals. `portal/navigation.blade.php` is shared
between desktop and mobile; `portal/icon.blade.php` defines the local SVG set.
Portal CSS is scoped under `.portal` in `resources/css/app.css` so administrator
pages keep their existing presentation.

All five contributor pages use the same shell. The report form retains its
duration/phone/history functionality. Home report status is loaded from the
existing personal API and refreshed after returning to the tab or changing the
WITA business date. Counts, schedules and attendance come from real data; no
mock users, task counts, fake team avatars or invented progress are displayed.

The original screenshot is a composition reference, not a phone-frame graphic:
no simulated iOS status bar or dynamic island is added to the web interface.
Node remains a local asset build tool; hosting serves PHP and compiled assets.

## Verification

85 PHPUnit tests / 481 assertions pass. Vite production build passes. Browser
checks cover all five pages at 320, 390, 768 and 1440 px with no horizontal
overflow or JavaScript errors, local font loading and one active navigation item.
Timer presets/start/pause/reset/modal and phone duration input were exercised
without submitting attendance or work-report records.
