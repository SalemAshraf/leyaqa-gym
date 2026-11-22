# Gamed Member App (Flutter)

Member-facing companion app for Gamed gyms: entry QR card, subscription
status, outstanding balance, and class self-booking with waitlist.

## How it connects

- The app talks to the gym platform's member API (`/api/member/*`) on the
  same server as the web app.
- Auth is the member's private **portal token** as a Bearer header. The gym
  shares the member's portal link (staff: member page → "Member account
  link"); the member pastes the link or token into the app once. It is the
  same token the web portal (`/my/{token}`) uses, and it is separate from
  the public door-scanner QR token.
- Server URL is set at build time:
  `flutter build apk --dart-define=GAMED_SERVER=https://gamed.mancydigitals.com`

## Status

Source complete against API v1 (profile, classes, bookings, book, cancel).
Not yet compiled here - run `flutter pub get` and `flutter analyze` /
`flutter build apk` on a machine with the Flutter SDK (>= 3.4) before
distribution. Play Store / App Store release is a separate owner decision.
