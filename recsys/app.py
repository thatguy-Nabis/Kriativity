from __future__ import annotations

import os
import time
import threading
from typing import Dict, List, Optional, Any

import numpy as np
import mysql.connector
from fastapi import FastAPI, Query, HTTPException, Header
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


app = FastAPI(title="Kriativity Content-Based Recommender", version="1.1")

# ----------------------------
# CONFIG
# ----------------------------
DB_CONFIG = {
    "host": os.getenv("KR_DB_HOST", "127.0.0.1"),
    "user": os.getenv("KR_DB_USER", "root"),
    "password": os.getenv("KR_DB_PASS", ""),
    "database": os.getenv("KR_DB_NAME", "content_discovery"),
    "port": int(os.getenv("KR_DB_PORT", "3306")),
}

# Optional API key for /reload
RELOAD_API_KEY = os.getenv("KR_RELOAD_KEY", "")  # set to something like "mysecret"
# If empty, /reload is open (fine for localhost only; not fine for public)

# TF-IDF settings
MAX_FEATURES = int(os.getenv("KR_MAX_FEATURES", "50000"))
NGRAM_MIN = int(os.getenv("KR_NGRAM_MIN", "1"))
NGRAM_MAX = int(os.getenv("KR_NGRAM_MAX", "2"))

# ----------------------------
# GLOBAL MODEL STATE
# ----------------------------
MODEL_LOCK = threading.RLock()

VECTORIZER: Optional[TfidfVectorizer] = None
ITEM_MATRIX = None  # sparse matrix
ITEM_IDS: List[int] = []
ID_TO_INDEX: Dict[int, int] = {}
LAST_RELOAD_TS: float = 0.0


def db_conn():
    # connection_timeout avoids hanging if DB is down
    return mysql.connector.connect(connection_timeout=3, **DB_CONFIG)


def build_item_text(row: Dict[str, Any]) -> str:
    """
    Build the text used for TF-IDF.
    You can extend this later (tags, artist name, etc).
    """
    title = (row.get("title") or "").strip()
    desc = (row.get("description") or "").strip()
    cat = (row.get("category") or "").strip()
    # Keep simple & robust
    return f"{title} {desc} {cat}".strip()


def reload_model() -> int:
    """
    Reloads the in-memory model from DB.
    Uses a lock so recommend() doesn't read partially-updated globals.
    """
    global VECTORIZER, ITEM_MATRIX, ITEM_IDS, ID_TO_INDEX, LAST_RELOAD_TS

    conn = db_conn()
    cur = conn.cursor(dictionary=True)

    try:
        cur.execute(
            """
            SELECT id, title, description, category
            FROM content
            WHERE is_published = 1
            ORDER BY id ASC
            """
        )
        rows = cur.fetchall()
    finally:
        cur.close()
        conn.close()

    ids = [int(r["id"]) for r in rows]
    docs = [build_item_text(r) for r in rows]

    # If there are zero items, reset model safely
    if not ids:
        with MODEL_LOCK:
            VECTORIZER = None
            ITEM_MATRIX = None
            ITEM_IDS = []
            ID_TO_INDEX = {}
            LAST_RELOAD_TS = time.time()
        return 0

    # IMPORTANT:
    # - We do NOT force english stopwords because your platform likely has Nepali + English mix.
    # - If you want stopwords, you can enable it later once you're sure language is mostly English.
    vectorizer = TfidfVectorizer(
        stop_words=None,
        ngram_range=(NGRAM_MIN, NGRAM_MAX),
        max_features=MAX_FEATURES,
        lowercase=True,
    )
    matrix = vectorizer.fit_transform(docs)

    with MODEL_LOCK:
        VECTORIZER = vectorizer
        ITEM_MATRIX = matrix
        ITEM_IDS = ids
        ID_TO_INDEX = {cid: i for i, cid in enumerate(ITEM_IDS)}
        LAST_RELOAD_TS = time.time()

    return len(ids)


@app.on_event("startup")
def on_startup():
    reload_model()


@app.get("/health")
def health():
    with MODEL_LOCK:
        ok = VECTORIZER is not None and ITEM_MATRIX is not None and len(ITEM_IDS) > 0
        return {
            "ok": ok,
            "items": len(ITEM_IDS),
            "last_reload_ts": LAST_RELOAD_TS,
        }


@app.post("/reload")
def reload_endpoint(x_api_key: Optional[str] = Header(default=None)):
    # Optional protection
    if RELOAD_API_KEY:
        if not x_api_key or x_api_key != RELOAD_API_KEY:
            raise HTTPException(status_code=401, detail="Unauthorized")

    n = reload_model()
    return {"ok": True, "items": n, "last_reload_ts": LAST_RELOAD_TS}


@app.get("/recommend")
def recommend(user_id: int = Query(...), limit: int = 10) -> Dict[str, Any]:
    """
    Content-Based Filtering (TF-IDF + cosine):
    - User profile = mean vector of liked items
    - Score = cosine similarity(user_profile, item_vector)
    - Cold start: trending fallback
    """
    limit = max(1, min(50, int(limit)))

    with MODEL_LOCK:
        has_model = ITEM_MATRIX is not None and len(ITEM_IDS) > 0 and ID_TO_INDEX

    if not has_model:
        return {
            "ok": True,
            "algorithm": "content_based_tfidf_cosine",
            "user_id": user_id,
            "recommendations": [],
        }

    conn = db_conn()
    cur = conn.cursor(dictionary=True)

    try:
        # liked content ids
        cur.execute("SELECT content_id FROM likes WHERE user_id = %s", (user_id,))
        liked_ids = [int(r["content_id"]) for r in cur.fetchall()]
        liked_set = set(liked_ids)

        # Cold-start fallback: trending
        if not liked_ids:
            cur.execute(
                """
                SELECT id AS content_id,
                       (COALESCE(views,0)*0.3 + COALESCE(likes,0)*0.7) AS score
                FROM content
                WHERE is_published = 1
                ORDER BY score DESC, created_at DESC
                LIMIT %s
                """,
                (limit,),
            )
            recs = [
                {"content_id": int(r["content_id"]), "score": float(r["score"])}
                for r in cur.fetchall()
            ]
            return {
                "ok": True,
                "algorithm": "popularity_fallback",
                "user_id": user_id,
                "recommendations": recs,
            }

        # Convert liked ids to indices
        with MODEL_LOCK:
            liked_indices = [ID_TO_INDEX[cid] for cid in liked_ids if cid in ID_TO_INDEX]
            matrix = ITEM_MATRIX
            item_ids = ITEM_IDS

        if not liked_indices:
            return {
                "ok": True,
                "algorithm": "no_valid_likes",
                "user_id": user_id,
                "recommendations": [],
            }

        # Mean profile vector (1 x features)
        user_vec = np.asarray(matrix[liked_indices].mean(axis=0))
        sims = cosine_similarity(user_vec, matrix).flatten()

        ranked = np.argsort(-sims)

        recs: List[Dict[str, float]] = []
        for idx in ranked:
            cid = item_ids[idx]
            # skip liked + near duplicates
            if cid in liked_set or sims[idx] > 0.98:
                continue
            score = float(sims[idx])
            if score <= 0:
                continue
            recs.append({"content_id": int(cid), "score": score})
            if len(recs) >= limit:
                break

        return {
            "ok": True,
            "algorithm": "content_based_tfidf_cosine",
            "user_id": user_id,
            "recommendations": recs,
        }

    finally:
        cur.close()
        conn.close()
