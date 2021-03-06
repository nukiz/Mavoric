<?php
/***
 *      __  __                       _      
 *     |  \/  |                     (_)     
 *     | \  / | __ ___   _____  _ __ _  ___ 
 *     | |\/| |/ _` \ \ / / _ \| '__| |/ __|
 *     | |  | | (_| |\ V / (_) | |  | | (__ 
 *     |_|  |_|\__,_| \_/ \___/|_|  |_|\___|
 *                                          
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 * 
 *  @author Bavfalcon9
 *  @link https://github.com/Olybear9/Mavoric                                  
 */

namespace Bavfalcon9\Mavoric\Core\Detections;

use Bavfalcon9\Mavoric\Main;
use Bavfalcon9\Mavoric\Mavoric;
use Bavfalcon9\Mavoric\Core\Utils\CheatIdentifiers;
use Bavfalcon9\Mavoric\events\MavoricEvent;
use Bavfalcon9\Mavoric\events\player\PlayerClick;
use Bavfalcon9\Mavoric\events\player\PlayerAttack;
use pocketmine\event\Listener;
use pocketmine\utils\TextFormat as TF;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\Server;


class AutoClicker implements Detection {
    private $mavoric;
    private $plugin;
    private $counters = [];

    public function __construct(Mavoric $mavoric) {
        $this->plugin = $mavoric->getPlugin();
        $this->mavoric = $mavoric;
    }

    public function onEvent(MavoricEvent $event): void {
        if ($event instanceof PlayerClick) {
            if ($event->isRightClick()) return;
            if ($event->clickedBlock()) return;

            $clicker = $event->getPlayer();
            $this->dueCheck($event, $clicker);
        }

        if ($event instanceof PlayerAttack) {
            $clicker = $event->getAttacker();
            $this->dueCheck($event, $clicker);
        }
    }

    public function isEnabled(): Bool {
        return true;
    }

    private function dueCheck($event, $clicker) {
        $amount = (!$this->plugin->config->getNested('AutoClicker.max-cps')) ? 22 : $this->plugin->config->getNested('AutoClicker.max-cps');
        if (!is_numeric($amount)) $amount = 22;

        $player = $clicker->getName();
        $time = microtime(true);

        if (!isset($this->counters[$player])) {
            $this->counters[$player] = [];
        }

        array_unshift($this->counters[$player], $time);
        
        if (!empty($this->counters[$player])) {
            $cps = count(array_filter($this->counters[$player], static function (float $t) use ($time) : bool {
                return ($time - $t) <= 1;
            }));
        }

        // AntiCheat checks
        if ($cps >= 100) {
            $event->issueViolation(CheatIdentifiers::CODES['AutoClicker'], 900);
            $event->sendAlert('AutoClicker', 'Interacted too quickly with ' . $cps . ' clicks per second');
            $event->getPlayer()->close('', 'Fragmented packet');
        }
        if ($cps >= $amount) {
            $event->issueViolation(CheatIdentifiers::CODES['AutoClicker']);
            $event->sendAlert('AutoClicker', 'Interacted too quickly with ' . $cps . ' clicks per second');
        }
    }
}