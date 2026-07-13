<?php

declare(strict_types=1);

namespace BotPvPBoxing\task;

use BotPvPBoxing\Main;
use pocketmine\scheduler\Task;

class ArenaTickTask extends Task {

    private Main $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun() : void {
        if(!$this->plugin->isEnabled()){
            return;
        }
        $this->plugin->getArenaManager()->tickAll();
    }
}
