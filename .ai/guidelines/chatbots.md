# Chatbots

- Domain config lives in `config/chatbots.php`; provider keys in published `config/ai.php`.
- Public Fit uses `App\Ai\Agents\PublicFitAgent` + `WorkHistoryKnowledgePack` only — never `ai_chunks`.
- Admin Recall uses `App\Ai\Agents\AdminRecallAgent` + ready `frontier_chats` RAG only — never the work-history pack.
- Neither agent implements `Conversational`; Fit is single-shot. Session rows are audit, not model history.
- Embeddings go through `App\Ai\Retrieval\CorpusEmbedder` only (`text-embedding-004` @ 768 unless re-indexed).
- Laravel Boost is guidelines/MCP for coding agents — not the runtime chat transport.
