// Accessibility checks are run manually against the Laravel Herd preview
// (https://apes-intranet.test) in Cursor's built-in browser.
// Playwright / Chromium automation was removed to stop browser popups.
//
//   BASE_URL=https://apes-intranet.test npm run test:a11y
//
// This script exits 0 and prints guidance until a Herd-friendly checker replaces it.

console.log('Accessibility: open https://apes-intranet.test in Cursor\'s browser.');
console.log('Playwright-based a11y automation has been removed from this repository.');
console.log('Keyboard and contrast checks are part of local Herd verification before ship-gate.');
process.exit(0);
