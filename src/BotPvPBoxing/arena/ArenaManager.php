<?php

declare(strict_types=1);

namespace BotPvPBoxing\arena;

use BotPvPBoxing\Main;
use pocketmine\utils\Config;

class ArenaManager {

    /** @var array<string, Arena> */
    private array $arenas = [];

    private Main $plugin;
    private Config $config;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->config = new Config($plugin->getDataFolder() . "arenas.yml", Config::YAML, []);
        $this->load();
    }

    private function load() : void {
        foreach($this->config->getAll() as $name => $data){
            if(!is_array($data)){
                continue;
            }
            $this->arenas[strtolower($name)] = Arena::fromArray((string) $name, $data);
        }
    }

    public function save() : void {
        $out = [];
        foreach($this->arenas as $arena){
            $out[$arena->getName()] = $arena->toArray();
        }
        $this->config->setAll($out);
        $this->config->save();
    }

    public function createArena(string $name, string $worldName) : bool {
        $key = strtolower($name);
        if(isset($this->arenas[$key])){
            return false;
        }
        $this->arenas[$key] = new Arena($name, $worldName);
        $this->save();
        return true;
    }

    public function removeArena(string $name) : bool {
        $key = strtolower($name);
        if(!isset($this->arenas[$key])){
            return false;
        }
        $this->arenas[$key]->reset($this->plugin, false);
        unset($this->arenas[$key]);
        $this->save();
        return true;
    }

    public function getArena(string $name) : ?Arena {
        return $this->arenas[strtolower($name)] ?? null;
    }

    /**
     * Finds the first arena that is fully configured and not currently in use.
     */
    public function findFreeArena() : ?Arena {
        foreach($this->arenas as $arena){
            if($arena->isFullyConfigured() && $arena->isFree()){
                return $arena;
            }
        }
        return null;
    }

    /**
     * @return array<string, Arena>
     */
    public function getAll() : array {
        return $this->arenas;
    }

    /**
     * Finds the arena a given player is currently fighting in, if any.
     */
    public function getArenaOf(\pocketmine\player\Player $player) : ?Arena {
        foreach($this->arenas as $arena){
            if($arena->getCurrentPlayer() !== null && $arena->getCurrentPlayer()->getId() === $player->getId()){
                return $arena;
            }
        }
        return null;
    }

    public function tickAll() : void {
        foreach($this->arenas as $arena){
            $arena->tick($this->plugin);
        }
    }
}
