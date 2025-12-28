# avik/pulse

Pulse is the runtime and HTTP kernel of the Avik framework.

It is responsible for driving the request–response lifecycle.

---

## Responsibilities

- Receive HTTP requests
- Run middleware pipeline
- Dispatch routes to controllers
- Convert exceptions into HTTP responses

---

## What Pulse Does NOT Do

- Define routes
- Define middleware
- Bootstrap the application
- Contain business logic

---

## Lifecycle

Request
 → Middleware
 → Controller
 → Response

---

## Design Principles

- Explicit execution flow
- No hidden magic
- Middleware-first architecture
- Framework-agnostic runtime

---

## Stability

The API of this package is frozen after v1.0.0.

---

## License

MIT
