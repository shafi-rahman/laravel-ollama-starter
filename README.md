# Laravel Ollama AI Starter

A production-ready AI chat API backend built with **Laravel 12** and **Ollama** (local LLM inference). No third-party API keys or cloud costs — runs entirely on your own machine.

Includes a built-in chat UI at `/chat` for local testing, SSE streaming, conversation memory, and a provider-agnostic architecture ready to plug in OpenAI or Claude.

---

## Preview

![Chat UI — streaming response in real time](priview-1.png)

![History and Logs tabs](priview-2.png)

---

## Features

- **Three response modes** — JSON (`/api/ai/chat`), chunked streaming (`/api/ai/stream`), Server-Sent Events (`/api/ai/sse`)
- **Conversation memory** — session-based, persisted to MySQL/SQLite
- **Multi-model support** — phi, llama3, gemma2, mistral — switch per request
- **Built-in chat UI** — real-time SSE streaming interface at `/chat`
- **API key authentication** — `X-API-Key` header on all AI endpoints
- **Rate limiting** — 60 requests/min per IP
- **Health check** — `GET /api/health` for monitoring
- **Provider-agnostic** — plug in OpenAI or Claude without changing core logic
- **Docker Compose** — one command to run MySQL + Ollama + Laravel

---

## Architecture

```
Client  →  /chat (browser UI)
             │
             ▼
Client  →  Laravel API  ──  ApiKeyMiddleware + throttle
                │
                ▼
           AIManager
           /         \
  MemoryService      OllamaProvider
       │                   │
    MySQL             Ollama  /api/chat
  (conversations       (local LLM)
   + messages)
```

---

## Prerequisites

| Requirement | Version |
|---|---|
| PHP | 8.2 or higher |
| Composer | 2.x |
| MySQL | 8.x (or use SQLite for quick start) |
| [Ollama](https://ollama.com) | Latest |

---

## Part 1 — Install Ollama

Ollama runs LLMs locally on your machine. Install it first.

### macOS
```bash
brew install ollama
```

### Windows
Download and run the installer from [ollama.com/download](https://ollama.com/download).

### Linux
```bash
curl -fsSL https://ollama.com/install.sh | sh
```

### Start Ollama
```bash
ollama serve
```

Ollama runs at `http://127.0.0.1:11434` by default. Verify it's running:

```bash
curl http://127.0.0.1:11434
# → Ollama is running
```

---

## Part 2 — Pull a Model

You must pull at least one model before using the API. Each model is downloaded once and cached locally.

### Recommended Models

| Model key | Pull command | Size | Notes |
|---|---|---|---|
| `phi` | `ollama pull phi` | ~1.6 GB | **Start here.** Fastest, lowest RAM |
| `llama3` | `ollama pull llama3` | ~4.7 GB | Better quality responses |
| `gemma2` | `ollama pull gemma2` | ~5.4 GB | Google model, strong reasoning |
| `mistral` | `ollama pull mistral` | ~4.1 GB | Balanced speed and quality |

### Quick start (pull phi — smallest model)
```bash
ollama pull phi
```

### Verify the model works
```bash
curl http://127.0.0.1:11434/api/chat -d '{
  "model": "phi",
  "messages": [{"role": "user", "content": "Hello"}],
  "stream": false
}'
```

You should get a JSON response with `"message": {"role": "assistant", "content": "..."}`.

### List downloaded models
```bash
ollama list
```

### Remove a model
```bash
ollama rm mistral
```

---

## Part 3 — Set Up the Laravel App

### 1. Clone the repo

```bash
git clone https://github.com/your-username/laravel-ollama-ai-starter.git
cd laravel-ollama-ai-starter/laravel-app
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and update these values:

```env
# Database — choose MySQL or SQLite below
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=open_claw
DB_USERNAME=root
DB_PASSWORD=your_password

# Ollama
OLLAMA_URL=http://127.0.0.1:11434/api/chat

# API authentication key (choose any secret string)
AI_API_KEY=my-secret-key
```

> **SQLite (zero config alternative):**
> Set `DB_CONNECTION=sqlite` and skip the MySQL setup. Laravel will create `database/database.sqlite` automatically.

### 4. Create the database (MySQL only)

```bash
mysql -u root -p -e "CREATE DATABASE open_claw;"
```

### 5. Run migrations

```bash
php artisan migrate
```

### 6. Start the server

```bash
php artisan serve
```

The app runs at `http://127.0.0.1:8000`.

---

## Part 4 — Test It

### Option A — Built-in Chat UI (Recommended for streaming)

Open [http://127.0.0.1:8000/chat](http://127.0.0.1:8000/chat) in your browser.

```
┌─────────────────────────────────────────────────────────┐
│  ⚡ Ollama Chat          session-abc123      [New Chat] │
├─────────────────────────────────────────────────────────┤
│  API Key [••••••••••••]  Model [phi ▾]  System [...]   │
├─────────────────────────────────────────────────────────┤
│                                                         │
│                                     You                 │
│                          ┌──────────────────────┐      │
│                          │ Explain Laravel       │      │
│                          └──────────────────────┘      │
│                                                         │
│  ┌────────────────────────────────────────────┐        │
│  │ Laravel is a PHP web application           │        │
│  │ framework that provides...  ▌              │        │
│  └────────────────────────────────────────────┘        │
│  Assistant                                              │
│                                                         │
├─────────────────────────────────────────────────────────┤
│  [ Type your message...                   ]  [Send]    │
└─────────────────────────────────────────────────────────┘
```

**Steps:**

1. Paste your `AI_API_KEY` value into the **API Key** field (saved in browser automatically)
2. Pick a model — start with **phi** (fastest)
3. Optionally set a **System** prompt, e.g. `You are a senior Laravel developer`
4. Type a message and press **Enter** (Shift+Enter for a new line)
5. Watch tokens stream in real time
6. Hit **New Chat** to start a fresh conversation session

> **Why use the UI for SSE?** Browser tools like Postman cannot display Server-Sent Events token-by-token. The built-in UI uses `fetch` + `ReadableStream` to show each token as it arrives — this is the only way to properly test streaming locally.

---

### Option B — Postman

Postman is ideal for testing the `/api/ai/chat` endpoint and inspecting JSON responses.

#### Step 1 — Create a Postman Environment

Go to **Environments → Add** and create `Ollama Local` with these variables:

| Variable | Initial Value |
|---|---|
| `base_url` | `http://127.0.0.1:8000` |
| `api_key` | `my-secret-key` ← your `AI_API_KEY` value |
| `session_id` | `postman-session-1` |

Select `Ollama Local` as the active environment.

#### Step 2 — Create a Collection

Create a new collection named **Laravel Ollama AI**. Add the following requests:

---

**Request 1 — Health Check**

- **Method:** `GET`
- **URL:** `{{base_url}}/api/health`
- **Headers:** *(none required)*

Expected response `200`:
```json
{ "status": "ok", "ollama": "reachable", "time": "..." }
```

---

**Request 2 — Chat (JSON response)**

- **Method:** `POST`
- **URL:** `{{base_url}}/api/ai/chat`
- **Headers:**

| Key | Value |
|---|---|
| `Content-Type` | `application/json` |
| `X-API-Key` | `{{api_key}}` |

- **Body → raw → JSON:**

```json
{
  "prompt": "Explain what Laravel is in 2 sentences",
  "session_id": "{{session_id}}",
  "model": "phi"
}
```

Expected response `200`:
```json
{
  "success": true,
  "model": "phi",
  "message": "Laravel is a PHP web application framework..."
}
```

---

**Request 3 — Chat with System Prompt**

Same as Request 2, change the body to:

```json
{
  "prompt": "Review this code: echo $x + 1;",
  "session_id": "{{session_id}}",
  "model": "llama3",
  "system": "You are a senior PHP developer. Give short, direct code reviews."
}
```

---

**Request 4 — Chat with Memory (multi-turn)**

Send these two requests **in order** using the same `session_id`. The second response will reference the first:

Request A body:
```json
{
  "prompt": "My name is Shafi",
  "session_id": "memory-test-1"
}
```

Request B body:
```json
{
  "prompt": "What is my name?",
  "session_id": "memory-test-1"
}
```

The model should respond with `Shafi` — proving memory works across turns.

---

**Request 5 — Streaming (chunked)**

- **Method:** `POST`
- **URL:** `{{base_url}}/api/ai/stream`
- Same headers and body format as Request 2

> In Postman you will see the full response only after it completes — not token by token. Use the browser UI at `/chat` for real-time streaming.

---

#### Step 3 — Common Postman Errors

| Error | Cause | Fix |
|---|---|---|
| `401 Unauthorized` | Wrong or missing API key | Check `X-API-Key` header matches `AI_API_KEY` in `.env` |
| `422 Unprocessable Content` | Missing required field | Add `prompt` and `session_id` to the request body |
| `503 Service Unavailable` | Ollama is not running | Run `ollama serve` in a terminal |
| `Could not get response` | Laravel not running | Run `php artisan serve` |

---

### Option C — cURL

**Health check (no auth)**
```bash
curl http://127.0.0.1:8000/api/health
```

**Standard chat**
```bash
curl -X POST http://127.0.0.1:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -H "X-API-Key: my-secret-key" \
  -d '{"prompt":"Explain Laravel","session_id":"test-1","model":"phi"}'
```

**SSE streaming (tokens print as they arrive)**
```bash
curl -X POST http://127.0.0.1:8000/api/ai/sse \
  -H "Content-Type: application/json" \
  -H "X-API-Key: my-secret-key" \
  -d '{"prompt":"List 3 Laravel features","session_id":"test-1","model":"phi"}'
```

**With system prompt**
```bash
curl -X POST http://127.0.0.1:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -H "X-API-Key: my-secret-key" \
  -d '{
    "prompt": "Review this: $x = 1 + 1;",
    "session_id": "test-2",
    "model": "llama3",
    "system": "You are a senior PHP code reviewer."
  }'
```

---

## API Reference

### Authentication

All AI endpoints require the `X-API-Key` header:

```
X-API-Key: your-secret-key
```

Returns `401` if missing or incorrect.

---

### `POST /api/ai/chat`

Non-streaming JSON response.

| Field | Type | Required | Description |
|---|---|---|---|
| `prompt` | string | Yes | User message |
| `session_id` | string | Yes | Conversation ID — reuse to continue a conversation |
| `model` | string | No | `phi` (default), `llama3`, `gemma2`, `mistral` |
| `system` | string | No | System prompt / persona |

**Response**
```json
{
  "success": true,
  "model": "phi",
  "message": "Laravel is a PHP web application framework..."
}
```

---

### `POST /api/ai/sse`

Server-Sent Events streaming. Best for browser clients.

Same request body as `/api/ai/chat`.

**Stream events**
```
event: message
data: Laravel

event: message
data:  is a PHP

event: done
data: true
```

---

### `POST /api/ai/stream`

Raw chunked streaming. Same request body as `/api/ai/chat`.

---

### `GET /api/health`

No authentication required.

```json
{
  "status": "ok",
  "ollama": "reachable",
  "time": "2026-05-04T12:00:00.000000Z"
}
```

---

## Docker Setup

Run everything (MySQL + Ollama + Laravel) with one command:

```bash
# 1. Copy and configure env
cp laravel-app/.env.example laravel-app/.env
# Edit laravel-app/.env and set AI_API_KEY

# 2. Start all services
docker-compose up -d

# 3. Run migrations
docker exec laravel_app php artisan migrate

# 4. Pull a model into the Ollama container
docker exec ollama ollama pull phi
```

| Service | URL |
|---|---|
| Laravel API | http://localhost:8000 |
| Chat UI | http://localhost:8000/chat |
| Ollama | http://localhost:11434 |
| MySQL | localhost:3306 |

---

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `AI_API_KEY` | API authentication key | — (required) |
| `OLLAMA_URL` | Ollama chat endpoint | `http://127.0.0.1:11434/api/chat` |
| `DB_CONNECTION` | `mysql` or `sqlite` | `mysql` |
| `DB_DATABASE` | Database name | `open_claw` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | — |

---

## Project Structure

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/AIController.php       # Chat, stream, SSE endpoints
│   │   └── Middleware/ApiKeyMiddleware.php     # X-API-Key authentication
│   ├── Models/
│   │   ├── Conversation.php                   # Session-based conversation
│   │   └── Message.php                        # Individual chat messages
│   └── Services/AI/
│       ├── AIManager.php                      # Orchestration layer
│       ├── MemoryService.php                  # DB persistence
│       ├── Contracts/AIProvider.php           # Provider interface
│       ├── DTOs/AIResponse.php                # Typed response object
│       └── Providers/OllamaProvider.php       # Ollama implementation
├── config/ai.php                              # Models, URLs, API key
├── database/migrations/                       # conversations + messages
├── resources/views/chat.blade.php             # Built-in chat UI
└── routes/
    ├── api.php                                # AI endpoints
    └── web.php                                # Chat UI route
```

---

## Troubleshooting

**`Ollama is unreachable`**
- Make sure Ollama is running: `ollama serve`
- Check `OLLAMA_URL` in `.env` matches where Ollama is running
- If using Docker, Ollama URL inside the container is `http://ollama:11434/api/chat` (already set in docker-compose)

**`Model [phi] not found in config`**
- The model key must exist in `config/ai.php` under `providers.ollama.models`
- Add it there and run `php artisan config:clear`

**Model responds slowly on first request**
- Normal — the model loads into RAM on first use
- Subsequent requests will be fast (`keep_alive` keeps it warm for 10 minutes)

**`401 Unauthorized`**
- Add the `X-API-Key` header with the value of `AI_API_KEY` from your `.env`

**`422 Unprocessable Entity`**
- `prompt` and `session_id` are required fields in the request body

---

## Adding a New Provider

1. Create `app/Services/AI/Providers/OpenAIProvider.php` implementing `AIProvider`:

```php
class OpenAIProvider implements AIProvider
{
    public function generate(array $messages, string $model): array { ... }
    public function stream(array $messages, string $model): mixed { ... }
}
```

2. Register in `AIManager::resolveProvider()`:
```php
'openai' => app(OpenAIProvider::class),
```

3. Add config in `config/ai.php`:
```php
'openai' => [
    'url'    => env('OPENAI_URL', 'https://api.openai.com/v1/chat/completions'),
    'models' => ['gpt4' => 'gpt-4o'],
],
```

---

## Adding a New Ollama Model

Any model available on [ollama.com/library](https://ollama.com/library) can be added:

```bash
# Pull the model
ollama pull codellama

# Add to config/ai.php
'models' => [
    'phi'       => 'phi:latest',
    'llama3'    => 'llama3:latest',
    'codellama' => 'codellama:latest',   # ← add here
],
```

Then pass `"model": "codellama"` in your API request.

---

## Roadmap

- [x] Request logging — duration, endpoint, model, status per request
- [x] History tab — browse past conversations and messages
- [x] Logs tab — request audit table in the chat UI
- [ ] Unit tests — mock provider, test AIManager in isolation
- [ ] Vector memory — embeddings + semantic search for long-term context
- [ ] OpenAI / Claude provider implementations

---

## License

MIT
