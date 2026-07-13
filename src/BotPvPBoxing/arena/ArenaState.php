<?php

declare(strict_types=1);

namespace BotPvPBoxing\arena;

/**
 * Represents the current lifecycle state of an Arena.
 */
enum ArenaState {
    /** No player is using the arena - it is free to be joined. */
    case WAITING;
    /** A player has joined and the pre-fight countdown is running. */
    case COUNTDOWN;
    /** The boxing match is actively in progress. */
    case FIGHTING;
    /** The match has just ended and the arena is resetting. */
    case ENDING;
}
