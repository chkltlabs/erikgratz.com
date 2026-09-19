<?php

return [
    'agents' => [
        'provider' => env('CHATBOT_PROVIDER', 'gemini'),
        'model' => env('CHATBOT_MODEL'),
        'timeout' => (int) env('CHATBOT_TIMEOUT', 60),
    ],

    'public' => [
        'rate_limit_per_minute' => (int) env('CHATBOT_PUBLIC_RATE_LIMIT', 10),
        'max_question_length' => 2000,
        'max_job_description_length' => 8000,
        'session_ttl_hours' => (int) env('CHATBOT_PUBLIC_SESSION_TTL_HOURS', 24),
    ],

    'rag' => [
        'top_k' => (int) env('CHATBOT_RAG_TOP_K', 6),
        'messages_per_chunk' => 4,
        'chunk_overlap_messages' => 1,
        'embedding' => [
            // Defaults to agents.provider when unset.
            'provider' => env('CHATBOT_EMBEDDING_PROVIDER'),
            // Pinned to the model/dimensions the existing ai_chunks index used.
            // Changing either requires re-indexing every imported conversation.
            'model' => env('CHATBOT_EMBEDDING_MODEL', 'text-embedding-004'),
            'dimensions' => (int) env('CHATBOT_EMBEDDING_DIMENSIONS', 768),
            'batch_size' => (int) env('CHATBOT_EMBEDDING_BATCH_SIZE', 32),
            'timeout' => (int) env('CHATBOT_EMBEDDING_TIMEOUT', 60),
        ],
    ],
];
