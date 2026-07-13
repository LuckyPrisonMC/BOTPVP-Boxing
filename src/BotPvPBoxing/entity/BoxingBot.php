<?php

declare(strict_types=1);

namespace BotPvPBoxing\entity;

use BotPvPBoxing\arena\Arena;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageEvent;

/**
 * The training/PvP bot that a player boxes against.
 *
 * It is implemented on top of pocketmine\entity\Human so that it renders
 * with a normal player skin and animations, but every bit of "AI" (chasing,
 * swinging, taking hits) is driven manually by ArenaTickTask - this entity
 * never uses PocketMine's damage/health system for the actual boxing logic,
 * it is only used for the visual swing/hurt animation and knockback.
 */
class BoxingBot extends Human {

    /** The arena this bot belongs to. Set right after spawning. */
    public ?Arena $arena = null;

    public function getName() : string {
        return "Boxing Bot";
    }

    /**
     * Bots are never saved into the world's chunk data - they are purely
     * transient and are (re)created whenever an arena is set up or a match
     * starts.
     */
    public function canSaveWithChunk() : bool {
        return false;
    }

    public function isFireProof() : bool {
        return true;
    }

    public function canBreathe() : bool {
        return true;
    }

    /**
     * Bots are immune to every normal damage source (fall damage, fire,
     * drowning, starvation, other mobs, etc). The only way its "hit count"
     * changes is through Arena/ArenaTickTask calling registerHitTaken().
     */
    public function attack(EntityDamageEvent $source) : void {
        $source->cancel();
    }

    protected function getInitialDragMultiplier() : float {
        return 0.02;
    }

    protected function getInitialGravity() : float {
        return 0.08;
    }

    /**
     * Builds a plain, solid-colour fallback skin so the bot can be spawned
     * even when no custom skin has been captured from a real player yet.
     */
    public static function makeDefaultSkin() : Skin {
        // 64x64 skin, RGBA, filled with a neutral navy-blue "jumpsuit" colour.
        $pixel = pack("C4", 30, 60, 110, 255);
        $data = str_repeat($pixel, 64 * 64);
        return new Skin("BotPvPBoxing_DefaultSkin", $data);
    }
}
