# Laravel Ollama AI Starter

A production-ready AI chat API backend built with **Laravel 12** and **Ollama** (local LLM inference). No third-party AI API keys required — runs entirely on your own machine or server.

## Features

- Three response modes: JSON, chunked streaming, Server-Sent Events (SSE)
- Conversation memory — session-based, persisted to database
- Multi-model support — switch between models per request
- Provider-agnostic architecture — plug in OpenAI, Claude, or Gemini without touching core logic
- API key authentication on all AI endpoints
- Rate limiting (60 requests / minute)
- Health check endpoint
- Docker Compose setup with MySQL, Ollama, and Laravel

## Architecture

```
Client
  │
  ▼
Laravel API  ──── ApiKeyMiddleware ──── throttle:60,1
  │
  ▼
AIController
  │
  ▼
AIManager
  ├── MemoryService  →  MySQL (conversations + messages)
  └── OllamaProvider →  Ollama  /api/chat
```

## Prerequisites

- PHP 8.2+
- Composer
- MySQL (or SQLite for local dev)
- [Ollama](https://ollama.com) installed and running locally

## Quick Start

### 1. Clone

```bash
git clone https://github.com/your-username/laravel-ollama-ai-starter.git
cd laravel-ollama-ai-starter/laravel-app
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set:

```env
DB_DATABASE=open_claw
DB_USERNAME=root
DB_PASSWORD=your_password

OLLAMA_URL=http://127.0.0.1:11434/api/chat
AI_API_KEY=your-secret-key
```

### 4. Run migrations

```bash
php artisan migrate
```

### 5. Pull an Ollama model

```bash
ollama pull phi
# or for better quality
ollama pull llama3
```

### 6. Start the server

```bash
php artisan serve
```

API is now available at `http://127.0.0.1:8000`.

---

## API Reference

### Authentication

Every AI endpoint requires the `X-API-Key` header matching `AI_API_KEY` in your `.env`.

```
X-API-Key: your-secret-key
```

---

### `POST /api/ai/chat`

Standard JSON response with full conversation memory.

**Request**

```json
{
  "prompt": "Explain what Laravel is",
  "session_id": "user-123",
  "model": "phi",
  "system": "You are a senior Laravel developer"
}
```

| Field | Required | Description |
|---|---|---|
| `prompt` | Yes | User message |
| `session_id` | Yes | Conversation identifier |
| `model` | No | `phi` (default) or `llama3` |
| `system` | No | System prompt / persona |

**Response**

```json
{
  "success": true,
  "model": "phi",
  "message": "Laravel is a PHP web framework..."
}
```

**cURL example**

```bash
curl -X POST http://127.0.0.1:8000/api/ai/chat \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key" \
  -d '{"prompt":"Explain Laravel","session_id":"abc123","model":"phi"}'
```

---

### `POST /api/ai/sse`

Server-Sent Events streaming. Best for browser clients using `EventSource`.

**Stream output**

```
event: message
data: Laravel is

event: message
data:  a PHP framework

event: done
data: true
```

**cURL example**

```bash
curl -X POST http://127.0.0.1:8000/api/ai/sse \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key" \
  -d '{"prompt":"Explain Laravel","session_id":"abc123"}'
```

---

### `POST /api/ai/stream`

Raw chunked streaming. Same request body as `/api/ai/chat`.

---

### `GET /api/health`

No authentication required. Returns Ollama connectivity status.

```json
{
  "status": "ok",
  "ollama": "reachable",
  "time": "2026-05-04T12:00:00.000000Z"
}
```

---

## Available Models

| Key | Ollama Model | Notes |
|---|---|---|
| `phi` | `phi:latest` | Fast, lightweight (3B) — default |
| `llama3` | `llama3:latest` | Better quality (8B) |

Add more models in `laravel-app/config/ai.php`:

```php
'models' => [
    'phi'     => 'phi:latest',
    'llama3'  => 'llama3:latest',
    'mistral' => 'mistral:latest',  // add any Ollama model
],
```

---

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `AI_API_KEY` | API authentication key | — |
| `OLLAMA_URL` | Ollama chat endpoint | `http://127.0.0.1:11434/api/chat` |
| `OLLAMA_MODEL` | Default model key | `phi` |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_DATABASE` | Database name | `open_claw` |

---

## Docker

```bash
# Copy env and set your API key
cp laravel-app/.env.example laravel-app/.env

docker-compose up -d

# Run migrations inside the container
docker exec laravel_app php artisan migrate

# Pull a model into the Ollama container
docker exec ollama ollama pull phi
```

Services:

| Service | Port |
|---|---|
| Laravel API | 8000 |
| MySQL | 3306 |
| Ollama | 11434 |
| OpenClaw | 3000 |

---

## Project Structure

```
laravel-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AIController.php       # Chat, stream, SSE endpoints
│   │   └── Middleware/
│   │       └── ApiKeyMiddleware.php   # X-API-Key auth
│   ├── Models/
│   │   ├── Conversation.php
│   │   └── Message.php
│   └── Services/AI/
│       ├── AIManager.php              # Orchestration
│       ├── MemoryService.php          # Conversation persistence
│       ├── Contracts/
│       │   └── AIProvider.php         # Provider interface
│       ├── DTOs/
│       │   └── AIResponse.php
│       └── Providers/
│           └── OllamaProvider.php     # Ollama implementation
├── config/
│   └── ai.php                         # Models, provider URLs, API key
├── database/migrations/               # conversations + messages tables
└── routes/
    └── api.php                        # All API routes
```

---

## Adding a New AI Provider

1. Create `app/Services/AI/Providers/OpenAIProvider.php` implementing `AIProvider`:

```php
class OpenAIProvider implements AIProvider
{
    public function generate(array $messages, string $model): array { ... }
    public function stream(array $messages, string $model): mixed { ... }
}
```

2. Register it in `AIManager::resolveProvider()`:

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

## Roadmap

- [ ] Logging middleware — response time, token usage per request
- [ ] Unit tests — mock provider, test AIManager in isolation
- [ ] Vector memory — embeddings + semantic search for long-term context
- [ ] OpenAI / Claude provider implementations

---

## License

MIT
