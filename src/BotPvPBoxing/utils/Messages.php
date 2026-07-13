<?php

declare(strict_types=1);

namespace BotPvPBoxing\utils;

use pocketmine\utils\TextFormat as TF;

/**
 * All player-facing text lives here, written in British English, so the
 * plugin's tone stays consistent and is easy to re-word later.
 */
final class Messages {

    public static function prefix() : string {
        return TF::BOLD . TF::GOLD . "[BoxingBot] " . TF::RESET;
    }

    public static function noPermission() : string {
        return self::prefix() . TF::RED . "You do not have permission to do that.";
    }

    public static function playersOnly() : string {
        return self::prefix() . TF::RED . "This command can only be used in-game.";
    }

    public static function unknownSubcommand() : string {
        return self::prefix() . TF::RED . "Unknown sub-command. Type " . TF::YELLOW . "/botpvp help" . TF::RED . " for a full list.";
    }

    public static function usage(string $usage) : string {
        return self::prefix() . TF::YELLOW . "Usage: " . TF::WHITE . $usage;
    }

    public static function arenaCreated(string $name) : string {
        return self::prefix() . TF::GREEN . "Arena '" . $name . "' has been created. Now set the bot spawn and player spawn with "
            . TF::YELLOW . "/botpvp setbotspawn " . $name . TF::GREEN . " and " . TF::YELLOW . "/botpvp setplayerspawn " . $name . TF::GREEN . ".";
    }

    public static function arenaAlreadyExists(string $name) : string {
        return self::prefix() . TF::RED . "An arena named '" . $name . "' already exists.";
    }

    public static function arenaRemoved(string $name) : string {
        return self::prefix() . TF::GREEN . "Arena '" . $name . "' has been removed.";
    }

    public static function arenaNotFound(string $name) : string {
        return self::prefix() . TF::RED . "No arena named '" . $name . "' could be found.";
    }

    public static function botSpawnSet(string $name) : string {
        return self::prefix() . TF::GREEN . "The bot's spawn point for arena '" . $name . "' has been set to your current position.";
    }

    public static function playerSpawnSet(string $name) : string {
        return self::prefix() . TF::GREEN . "The player's spawn point for arena '" . $name . "' has been set to your current position.";
    }

    public static function lobbySet() : string {
        return self::prefix() . TF::GREEN . "The lobby spawn (where players return to after a match) has been set to your current position.";
    }

    public static function lobbyNotSet() : string {
        return self::prefix() . TF::RED . "The lobby spawn has not been configured yet. An administrator should run /botpvp setlobby.";
    }

    public static function speedSet(string $name, int $ticks) : string {
        return self::prefix() . TF::GREEN . "The bot's attack speed for arena '" . $name . "' is now " . $ticks . " ticks between hits.";
    }

    public static function rangeSet(string $name, float $range) : string {
        return self::prefix() . TF::GREEN . "The bot's attack range for arena '" . $name . "' is now " . $range . " blocks.";
    }

    public static function skinSet(string $name, string $fromPlayer) : string {
        return self::prefix() . TF::GREEN . "The bot in arena '" . $name . "' now wears " . $fromPlayer . "'s skin.";
    }

    public static function arenaIncomplete() : string {
        return self::prefix() . TF::RED . "That arena is not fully configured yet (missing bot spawn or player spawn). Please contact an administrator.";
    }

    public static function noFreeArena() : string {
        return self::prefix() . TF::RED . "Every arena is currently occupied. Please wait a moment and try again.";
    }

    public static function alreadyInArena() : string {
        return self::prefix() . TF::RED . "You are already in a Bot PvP Boxing match. Use " . TF::YELLOW . "/botpvp leave" . TF::RED . " to exit first.";
    }

    public static function notInArena() : string {
        return self::prefix() . TF::RED . "You are not currently in a Bot PvP Boxing match.";
    }

    public static function joinedArena(string $name) : string {
        return self::prefix() . TF::GREEN . "You have joined arena '" . $name . "'. Get ready to fight!";
    }

    public static function leftArena() : string {
        return self::prefix() . TF::YELLOW . "You have left the boxing match.";
    }

    public static function countdownTitle(int $seconds) : string {
        return TF::BOLD . TF::YELLOW . (string) $seconds;
    }

    public static function fightTitle() : string {
        return TF::BOLD . TF::GREEN . "FIGHT!";
    }

    public static function actionBar(int $playerHits, int $botHits, int $target) : string {
        return TF::AQUA . "Your Hits: " . TF::WHITE . $playerHits . TF::GRAY . " / " . $target
            . TF::RESET . "   " . TF::RED . "Bot Hits: " . TF::WHITE . $botHits . TF::GRAY . " / " . $target;
    }

    public static function victoryTitle() : string {
        return TF::BOLD . TF::GREEN . "VICTORY!";
    }

    public static function victorySubtitle(int $playerHits, int $botHits) : string {
        return TF::WHITE . "You won " . $playerHits . " - " . $botHits . "!";
    }

    public static function defeatTitle() : string {
        return TF::BOLD . TF::RED . "DEFEAT!";
    }

    public static function defeatSubtitle(int $playerHits, int $botHits) : string {
        return TF::WHITE . "The bot beat you " . $botHits . " - " . $playerHits . ". Better luck next time!";
    }

    public static function arenaList(array $names) : string {
        if(count($names) === 0){
            return self::prefix() . TF::YELLOW . "There are no arenas set up yet.";
        }
        return self::prefix() . TF::YELLOW . "Arenas (" . count($names) . "): " . TF::WHITE . implode(TF::GRAY . ", " . TF::WHITE, $names);
    }
}
