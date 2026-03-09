# DDbuildingTech Flutter Mobile App

This app connects directly to your website backend APIs in `/dd4/api`.

## Features

- Products listing from `products.php?action=get_all`
- Product details view + add-to-cart from list/details
- In-app cart with quantity updates and order creation (`orders.php?action=create`)
- Customer support ticket submission via `support.php`
- Installation request submission via `solar_requests.php`
- In-app API URL setting for local/live server switching
- Login/Register via `auth.php`
- Persistent auth token + profile session
- "My Requests" screen with pull-to-refresh + load-more pagination
- Local notification alerts for support/installation status updates (polled from `support.php?action=notifications`)

## Run locally

From `flutter_mobile_app/`:

```bash
flutter pub get
flutter run --dart-define=API_BASE_URL=http://localhost/dd4/api
```

On Windows, enable Developer Mode once if Flutter asks for symlink support:

```powershell
start ms-settings:developers
```

For live server:

```bash
flutter run --dart-define=API_BASE_URL=https://yourdomain.com/dd4/api
```

## Build release

```bash
flutter build appbundle --release --dart-define=API_BASE_URL=https://yourdomain.com/dd4/api
```
