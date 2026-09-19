# AI chatbots

Two isolated assistants share LLM provider config but never share retrieval corpora.

| Surface | Audience | Knowledge | Entry |
|---------|----------|-----------|-------|
| Public fit bot | Anyone | Runtime pack from site work history | `/fit` |
| Admin recall bot | Filament users | RAG over imported frontier chats | `/admin` → AI group |

## Isolation rules

- Public answers use only `WorkHistoryKnowledgePack` (from [`config/work-history.php`](../config/work-history.php) + [`config/portfolio.php`](../config/portfolio.php)). They never query `ai_chunks`.
- Admin recall retrieves only **ready** `frontier_chats` documents. Pending / indexing / failed imports are invisible to recall.
- Updating a job bullet in `config/work-history.php` updates both the Experience page and the public bot (pack is assembled at request time).
- Do not seed frontier chats into the public pack. Do not auto-ingest Experience into `ai_chunks` in v1 (`work_history` corpus is reserved for a later promotion).

## Stack

- **Agents:** [`PublicFitAgent`](../app/Ai/Agents/PublicFitAgent.php) / [`AdminRecallAgent`](../app/Ai/Agents/AdminRecallAgent.php) via `laravel/ai` (`Promptable` + `AnswersSafely`). Neither implements `Conversational` — Fit stays single-shot.
- **Embeddings:** [`CorpusEmbedder`](../app/Ai/Retrieval/CorpusEmbedder.php) is the only caller of `Embeddings::for()`. Pins `text-embedding-004` @ 768 dims (override via env; changing either requires re-index).
- **Similarity:** Postgres `pgvector` cosine distance (`<=>`) on the `pgsql` connection (`PG_*` env). MySQL stays the default app database.
- **Boost:** `laravel/boost` is install-time guidelines/MCP for agents — not a runtime chat transport.

## Data model

```
ai_imported_conversations  →  ai_imported_messages
           ↓
      ai_documents (corpus, kind)
           ↓
        ai_chunks (MySQL content + embedding_model)
           ↓
        ai_chunk_embeddings (Postgres vector(768))

ai_sessions / ai_session_messages  → audit / debug turns (public uses opaque capability hashes; not RAG sources)
```

Chunk text lives on MySQL. Vectors live in a dedicated `erikgratz_vectors` database. `php artisan migrate` and `migrate:fresh` run the `pgsql` path automatically (`$connection = 'pgsql'`; `db:wipe` also drops that schema). Changing embedding dimensions requires a new vector migration and re-index.

## Config

- Domain: [`config/chatbots.php`](../config/chatbots.php) — agents, public limits, RAG
- Providers: [`config/ai.php`](../config/ai.php) — published laravel/ai providers (defaults Gemini)
- Env: see `.env.example` (`GEMINI_API_KEY` / `AI_API_KEY`, `CHATBOT_*`, `OLLAMA_URL`)
- `TRUSTED_PROXIES` — empty ignores `X-Forwarded-For` (fail-closed); set edge CIDRs in production
- Tests fake with `PublicFitAgent::fake()` / `AdminRecallAgent::fake()` / `Embeddings::fake()` (no `AI_FAKE`)

## Admin ingest (v1 — paste / upload)

1. Filament **AI → Imported chats → Import chat**: paste Gemini JSON, `User:` / `Assistant:` plaintext, or Google Takeout **My Activity** HTML (AI Mode `outer-cell` cards), or upload `.json` / `.txt` / `.md` / `.html` (max 20 MB; file deleted after read).
2. Parsers: `GoogleTakeoutHtmlParser` (HTML → many chats), `GeminiExportParser`, else `PlaintextFrontierChatParser`.
3. Takeout splits one file into many conversations by `outer-cell`. Display title = `Searched for` link text. Stable upsert id = hash of **first prompt + first card timestamp** (continues update the same row).
4. Upsert key is `import_key` (`external:{source}:{id}` or `content:{source}:{sha256}` for plaintext). Re-uploads replace messages, bump `revision`, and re-queue indexing. Conversations missing from a newer Takeout are left alone.
5. `ProcessImportedConversation` (unique per conversation) embeds outside a transaction, then publishes chunks and sets `status=ready` atomically. Re-index sets `indexing` first so recall skips the conversation until publish.
6. Browse under **AI → Imported chats**; ask under **AI → Recall chats**.

### Supported Gemini JSON shapes

- Top-level `contents`, `messages`, or `turns` arrays
- Message `role` / `author` with `parts[].text`, `content`, or `text`
- Optional `title`, `model`, `id` / `conversationId`

### Google Takeout HTML (AI Mode)

- Export **My Activity → AI Mode** as HTML (`MyActivity.html`)
- Each `div.outer-cell` is one conversation; Lens-only cards without `Your prompt:` are skipped
- Turns pair `Your prompt:` (user) with `Search's response:` (assistant)
- Message bodies are converted **HTML → Markdown** on import (tables, lists, emphasis preserved for RAG and Recall)
- Re-upload the same Takeout file to refresh continued chats and re-index; markdown structure only appears after a fresh import/upsert under this converter

## Public fit bot

- Route: `GET /fit` (page throttle); asks go through Livewire and an app-level rate limit
- Single-shot assessment (no multi-turn model history); optional job description + question
- Opaque `sessionToken` capability (hash stored); scoped to `surface=public` + visitor hash
- Provider errors never render response bodies on the public page
- Family A editorial UI (experience-style cards), not chat bubbles

## Future phase B — provider API sync (not implemented)

Design intent only; v1 paste/upload remains the supported path.

1. Add provider sync adapters that call the same `FrontierChatImporter::persist()` / parser entrypoints.
2. Gemini (or Google AI Studio export) polling or webhooks create/update `ai_imported_conversations` using `import_key` / `external_id` upsert — no schema fork.
3. Dispatch the existing `ProcessImportedConversation` job after sync.
4. Store API tokens in env / secrets (same pattern as other integrations); do not log secrets inside `raw_payload` beyond what the export already contains.
5. Optional: scheduled artisan command `ai:sync-frontier-chats` with `--source=gemini`.

When B ships, document auth scopes, rate limits, and which export fields map to `external_id`.
