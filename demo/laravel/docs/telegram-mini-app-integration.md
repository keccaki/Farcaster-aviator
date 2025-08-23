# Telegram Mini-App Integration

This guide covers integrating the Aviator crash game as a Telegram Web App without modifying core game logic.

## 1. Environment Setup

- Add your Telegram bot token to `.env`:
  ```dotenv
  TELEGRAM_BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
  ```
- Ensure `config/services.php` includes:
  ```php
  'telegram' => [
      'bot_token' => env('TELEGRAM_BOT_TOKEN'),
  ],
  ```

## 2. Routes

| Method | URI                 | Handler                                   | Purpose                                    |
| ------ | ------------------- | ----------------------------------------- | ------------------------------------------ |
| GET    | `/telegram-webapp`  | `Route::view('telegram-webapp')`          | Renders Web App wrapper with Telegram API  |
| POST   | `/telegram/initdata`| `TelegramWebAppController@init`           | Verify signed `initData` and store session|
| POST   | `/telegram/results` | `TelegramWebAppController@sendResult`     | Relay game results back via Bot API        |

## 3. Wrapper View

File: `resources/views/telegram-webapp.blade.php`

- Loads Telegram JS API:
  ```html
  <script src="https://telegram.org/js/telegram-web-app.js"></script>
  ```
- On load:
  ```js
  Telegram.WebApp.ready();
  Telegram.WebApp.expand();
  ```
- Sends `initData` to backend:
  ```js
  fetch(route('telegram.initdata'), { method:'POST', headers:{...},
    body: JSON.stringify({ initData: Telegram.WebApp.initData })
  })
  ```
- Hides main button and adds an Exit button:
  ```js
  Telegram.WebApp.MainButton.hide();
  document.getElementById('close-button').onclick = () => Telegram.WebApp.close();
  ```
- Embeds existing game via `<iframe src="/game">`.

## 4. Services

- **TelegramWebAppService** (`app/Services/TelegramWebAppService.php`):
  - Parses and verifies `initData` HMAC against `bot_token`.
  - Throws `InvalidArgumentException` on failure.

- **TelegramBotService** (`app/Services/TelegramBotService.php`):
  - Wraps Bot API calls (`sendMessage(chat_id, text, options)`)
  - Uses HTTP client to post to `https://api.telegram.org/bot<token>/sendMessage`.

## 5. Controller

`TelegramWebAppController` handles:

- `init(Request $r, TelegramWebAppService $s)`:
  - Validates `initData`
  - Calls `$s->verifyInitData(...)`
  - Stores parsed user in session key `telegram_user`
  - Returns JSON of user data

- `sendResult(Request $r, TelegramBotService $b)`:
  - Validates `crashPoint` and `betAmount`
  - Reads `telegram_user.id` from session
  - Formats a summary message and calls `$b->sendMessage(...)`
  - Returns Bot API response JSON

## 6. Testing

- **Unit tests**: `tests/Unit/TelegramWebAppServiceTest.php`
  - Cover valid, missing-hash, and invalid-signature scenarios.

- **Feature tests**: `tests/Feature/TelegramWebAppControllerTest.php`
  - `testInitDataEndpointSuccess` (200 + JSON + session)
  - `testInitDataEndpointValidationError` (422)
  - `testSendResultUnauthorized` (403)
  - `testSendResultSuccess` (200 + mocked API)

## 7. Workflow

1. User clicks “Start Web App” in Telegram bot → opens `/telegram-webapp` in WebView.
2. Wrapper verifies user identity via `initData` HMAC.
3. Crash game runs as usual in iframe.
4. On game end, client posts results to `/telegram/results`.
5. Laravel relays a message back into the Telegram chat.

---

All integration is decoupled in a narrow wrapper so your existing `/game` code and APIs remain unchanged. 