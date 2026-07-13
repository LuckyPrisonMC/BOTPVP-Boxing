<?php

declare(strict_types=1);

namespace BotPvPBoxing\listener;

use BotPvPBoxing\arena\ArenaState;
use BotPvPBoxing\entity\BoxingBot;
use BotPvPBoxing\Main;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;

class PlayerListener implements Listener {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Handles a player punching a Boxing Bot: this is the ONLY place player
     * hits are counted, and the bot never actually loses health here - the
     * real damage is always cancelled, we just register the hit.
     */
    public function onDamage(EntityDamageByEntityEvent $event) : void {
        $victim = $event->getEntity();
        $damager = $event->getDamager();

        if(!($victim instanceof BoxingBot)){
            return;
        }

        // The bot is always invulnerable to real damage - this also covers
        // hits from anyone other than the arena's assigned player below.
        $event->cancel();

        if(!($damager instanceof Player) || $victim->arena === null){
            return;
        }

        $arena = $victim->arena;
        $currentPlayer = $arena->getCurrentPlayer();
        if($currentPlayer === null || $currentPlayer->getId() !== $damager->getId()){
            // Someone who isn't the assigned fighter took a swing - ignore it.
            return;
        }
        if($arena->getState() !== ArenaState::FIGHTING){
            return;
        }

        $arena->registerPlayerHit($victim);

        // Small knockback on the bot so hits feel responsive.
        $dx = $victim->getPosition()->x - $damager->getPosition()->x;
        $dz = $victim->getPosition()->z - $damager->getPosition()->z;
        $victim->knockBack($dx, $dz, 0.3);
    }

    public function onQuit(PlayerQuitEvent $event) : void {
        $player = $event->getPlayer();
        $arena = $this->plugin->getArenaManager()->getArenaOf($player);
        if($arena !== null){
            $arena->reset($this->plugin, false);
        }
    }
}
