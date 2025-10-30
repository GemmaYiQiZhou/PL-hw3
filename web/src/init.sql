BEGIN;

-- drops
DROP VIEW  IF EXISTS hw3_user_stats CASCADE;
DROP TABLE IF EXISTS hw3_guesses CASCADE;
DROP TABLE IF EXISTS hw3_games   CASCADE;
DROP TABLE IF EXISTS hw3_words   CASCADE;
DROP TABLE IF EXISTS hw3_users   CASCADE;


CREATE TABLE hw3_users (
    user_id        BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name           TEXT        NOT NULL,
    email          TEXT        NOT NULL,
    password_hash  TEXT        NOT NULL, 
    created_at     TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- case-insensitive uniqueness on email
CREATE UNIQUE INDEX hw3_users_email_uniq ON hw3_users (LOWER(email));

-- ---------- 7-letter Target Words (the bank of words that have been played)
CREATE TABLE hw3_words (
    word_id   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    word      TEXT NOT NULL UNIQUE,
    added_at  TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT hw3_words_len_chk CHECK (char_length(word) = 7)
);

-- ---------- Games (one row per game played)
-- A game ties a user to a single 7-letter target word.
CREATE TABLE hw3_games (
    game_id         BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id         BIGINT NOT NULL REFERENCES hw3_users(user_id) ON DELETE CASCADE,
    target_word_id  BIGINT NOT NULL REFERENCES hw3_words(word_id) ON DELETE RESTRICT,
    started_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ended_at        TIMESTAMPTZ,
    won             BOOLEAN NOT NULL DEFAULT FALSE,  -- true iff 7-letter target guessed
    score           INTEGER NOT NULL DEFAULT 0,
    invalid_guesses INTEGER NOT NULL DEFAULT 0
);

-- Prevent giving the same user the same target word again (ever)
CREATE UNIQUE INDEX hw3_games_user_word_once
    ON hw3_games(user_id, target_word_id);

CREATE INDEX hw3_games_user_idx ON hw3_games(user_id);
CREATE INDEX hw3_games_word_idx ON hw3_games(target_word_id);

-- ---------- Guesses (all guesses made within a game)
CREATE TABLE hw3_guesses (
    guess_id   BIGINT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    game_id    BIGINT NOT NULL REFERENCES hw3_games(game_id) ON DELETE CASCADE,
    guess      TEXT   NOT NULL,           -- the raw guess as entered
    is_valid   BOOLEAN NOT NULL,          -- valid per dictionary & letters
    is_target  BOOLEAN NOT NULL DEFAULT FALSE,  -- equals the 7-letter target
    len        SMALLINT GENERATED ALWAYS AS (char_length(guess)) STORED,
    points     INTEGER NOT NULL DEFAULT 0,      -- computed in app from len if valid
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX hw3_guesses_game_idx ON hw3_guesses(game_id);
CREATE INDEX hw3_guesses_valid_idx ON hw3_guesses(is_valid);

DROP VIEW IF EXISTS hw3_user_stats CASCADE;

CREATE TABLE hw3_user_stats (
    user_id BIGINT PRIMARY KEY,
    games_played INT DEFAULT 0,
    games_won INT DEFAULT 0,
    highest_score INT DEFAULT 0,
    average_score DECIMAL(5,2) DEFAULT 0,
    win_pct DECIMAL(5,2) DEFAULT 0
);