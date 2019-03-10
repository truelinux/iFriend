<?php
/**
 * author: LilCrispy2o9/Angelo Vidrio
 */
namespace iFriend;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\command\CommandExecutor;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\plugin\PluginManager;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
class Main extends PluginBase  implements Listener {
	
	
	public $verify;
        public $provider;
        public $temp = array();
        private $db;
	
    public function onEnable(){
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "Players/");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
            "players-in-same-group-are-friendly" => true,
            "friends-are-friendly" => true,
            "max-friends" => 8,
            "provider" => "SQL"
        ));
        if(!$this->getServer()->getPluginManager()->getPlugin("PurePerms")) {
            $this->getLogger()->info( TextFormat::RED . "PurePerms Not Loaded With iFriend!" );
            $this->verify = false;
        }else{
            $this->pure = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
            $this->getLogger()->info( TextFormat::GREEN . "PurePerms Loaded With iFriend!" );
            $this->verify = true;
        }
        if($this->getConfig()->get("provider") == "SQL") {
            $this->provider = "SQL";
            $this->db = new \SQLite3($this->getDataFolder() . "iFriend.db");
            $this->db->exec("CREATE TABLE IF NOT EXISTS friends(p1 VARCHAR, p2 VARCHAR);");
            $this->getLogger()->info(TextFormat::GREEN . "Using SQL provider.");
        } elseif($this->getConfig()->get("provider") == "YML") {
            $this->provider = "YML";
            $this->getLogger()->info(TextFormat::GREEN . "Using YML provider.");
        }
    }
	public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
            if($sender instanceof Player) {
                $player = strtolower($sender->getName());
                $pc = $sender->getName();
                if(strtolower($command->getName()) == "friend") {
                    if(empty($args)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/friend <player-name> OR [accept/decline]");
                            return true;
                    }
                    if(strtolower($args[0]) !== "accept" && strtolower($args[0]) !== "decline" && strtolower($args[0]) !== "tp" && strtolower($args[0]) !== "list") {
                        if(!isset($args[0])) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/friend <player-name> OR [accept/decline]");
                            return true;
                        }
                        $max = $this->getConfig()->get("max-friends");
                        if(count($this->getAllFriends($player)) == $max) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] You have the max amount of friends!");
                            return;
                        }
                        $friendexact =  $this->getServer()->getPlayer($args[0]);
                        if(!$friendexact instanceof Player) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player not online!");
                            return true;
                        }
                        $friend = strtolower($friendexact->getName());
                        $fc = $friendexact->getName();
                        if($this->areFriends($player, $friend)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] '$fc' is already your friend!");
                            return true;
                        }
                        if($this->getUserTEMP($player, $friend)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] You already sent an request to '$friend'");
                            return true;
                        }
                        if(!file_exists($this->getDataFolder() . "Players/" . $player . ".yml")) {
                            $this->temp[$player] = $friend;
                            $sender->sendMessage(TextFormat::GOLD . "[iFriend] '$fc' was asked to be your friend.");
                            $friendexact->sendMessage(TextFormat::GOLD . "[iFriend] '$pc' wants to be your friend!\n".TextFormat::GRAY."Do \"/friend accept $pc\" to accept\n".TextFormat::GRAY."OR\n".TextFormat::GRAY."Do \"/friend decline $pc\" to decline");
                            return true;
                        }
                        if(file_exists($this->getDataFolder() . "Players/" . $player . ".yml")) {
                            $this->temp[$player] = $friend;
                            $sender->sendMessage(TextFormat::GOLD . "[iFriend] '$fc' was asked to be your friend.");
                            $friendexact->sendMessage(TextFormat::GOLD . "[iFriend] '$pc' wants to be your friend!\n".TextFormat::GRAY."Do \"/friend accept $pc\" to accept\n".TextFormat::GRAY."OR\n".TextFormat::GRAY."Do \"/friend decline $pc\" to decline");
                            return true;
                        }
                    }
                    if(strtolower($args[0]) == "accept") {
                        if(empty($args[1])) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/friend [decline/accept] <player-name>");
                            return true;
                        }
                        $friendexact =  $this->getServer()->getPlayer($args[1]);
                        if(!$friendexact instanceof Player) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player not online!");
                            return true;
                        }
                        $getsender = strtolower($friendexact->getName());
                        $friendname = $friendexact->getName();
                        if(!$this->getUserTEMP($getsender, $player)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player has not sent you a request!");
                            return true;
                        }
                        $playerget = strtolower($sender->getName());
                        $this->removeUserTEMP($getsender, strtolower($sender->getName()));
                        $this->setUser($getsender, $player);
                        $sender->sendMessage(TextFormat::GOLD . "[iFriend] Request Accepted!");
                        $sender->sendMessage(TextFormat::GOLD . "[iFriend] '$friendname' can now teleport to you.");
                        $friendexact->sendMessage(TextFormat::GOLD . "[iFriend] Your request to '$player' was accepted!");
                        return true;
                    }
                    if(strtolower($args[0]) == "decline") {
                        if(empty($args[1])) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/friend [decline/accept] <player-name>");
                            return true;
                        }
                        $friendexact =  $this->getServer()->getPlayer($args[1]);
                        if(!$friendexact instanceof Player) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player not online!");
                            return true;
                        }
                        $getsender = strtolower($friendexact->getName());
                        if(!$this->getUserTEMP($getsender, $player)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player has not sent you a request!");
                            return true;
                        }
                        $this->removeUserTEMP($getsender, $player);
                        $sender->sendMessage(TextFormat::GOLD . "[iFriend] Request Declined!");
                        $friendexact->sendMessage(TextFormat::GOLD . "[iFriend] Your request to '$player' was declined!");
                        return true;
                    }
                    if(strtolower($args[0]) == "tp") {
                        if(empty($args[1])) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/friend tp <player-name>");
                            return true;
                        }
                        $friend = $this->getServer()->getPlayer($args[1]);
                        if(!$friend instanceof Player) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] Player not online!");
                            return true;
                        }
                        $fname = strtolower($friend->getName());
                        $fc = $friend->getName();
                        if(!$this->areFriends($player, $fname)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] '$fc' is not your friend!");
                            return true;
                        }
                        $name = $sender->getName();
                        $sender->teleport($friend->getPosition(), $friend->getYaw(), $friend->getPitch());
                        $sender->sendMessage(TextFormat::GOLD . "Teleporting to '$fc'...");
                        $friend->sendMessage(TextFormat::GOLD . "'$name' has teleported to you.");
                        return true;
                    }
                    if(strtolower($args[0]) == "list") {
                        if(!$this->hasFriends($player)) {
                            $sender->sendMessage(TextFormat::RED . "[iFriend] You have no friends!");
                            return;
                        }
                        $friends = $a = new Config($this->getDataFolder() . "Players/" . $player . ".yml", CONFIG::YAML);
                        $msg = null;
                        foreach($this->getAllFriends($player) as $friend => $p) {
                            if($friend == 'p2') {
                                $msg .= "$p, ";
                            }
                        } 
                        $sender->sendMessage(TextFormat::GRAY . "Friends: $msg");
                        return;
                    }
                }
                if(strtolower($command->getName()) == "unfriend") {
                    if(empty($args[0])) {
                        $sender->sendMessage(TextFormat::RED . "[iFriend] Usage:/unfriend <player-name>");
                        return true;
                    }
                    $friend = strtolower($args[0]);
                    if(!$this->areFriends($player, $friend)) {
                        $sender->sendMessage(TextFormat::RED . "[iFriend] '$friend' is not your friend!");
                        return true;
                    }
                    if($this->areFriends($player, $friend)) {
                        $this->removeUser($player, $friend);
                        $sender->sendMessage(TextFormat::GOLD . "[iFriend] '$friend' is no longer your friend!");
                    }
                }
            }
		return true;
        }
        public function getAllFriends($p1) {
            if($this->provider == "SQL") {
                $a = $this->db->query("SELECT * FROM friends WHERE p1='$p1';");
                $b = $a->fetchArray(SQLITE3_ASSOC);
                return $b;
            }
            if($this->provider == "YML") {
                $a = new Config($this->getDataFolder() . "Players/" . $p1 . ".yml", CONFIG::YAML);
                $b = $a->getAll();
                return $b;
            }
        }
	public function removeUser($p1, $p2) {
            if($this->provider == "SQL") {
                $this->db->query("DELETE FROM friends WHERE p1='$p1' AND p2='$p2';");
                $this->db->query("DELETE FROM friends WHERE p1='$p2' AND p2='$p1';");
                return;
            }
            if($this->provider == "YML") {
                $a = new Config($this->getDataFolder() . "Players/" . $p1 . ".yml", CONFIG::YAML);
                $a->remove($p2);
                $a->save();
                $a = new Config($this->getDataFolder() . "Players/" . $p2 . ".yml", CONFIG::YAML);
                $a->remove($p1);
                $a->save();
                return;
            }
        }
	public function setUser($p1, $p2) {
            if($this->provider == "SQL") {
                $a = $this->db->prepare("INSERT INTO friends (p1, p2) VALUES (:p1, :p2);");
                $a->bindValue(":p1", $p1);
                $a->bindValue(":p2", $p2);
                $result = $a->execute();
                $a = $this->db->prepare("INSERT INTO friends (p1, p2) VALUES (:p1, :p2);");
                $a->bindValue(":p1", $p2);
                $a->bindValue(":p2", $p1);
                $result = $a->execute();
                return;
            }
            if($this->provider == "YML") {
                $a = new Config($this->getDataFolder() . "Players/" . $p1 . ".yml", CONFIG::YAML);
                $a->set("$p2", "TRUE");
                $a->save();
                $a = new Config($this->getDataFolder() . "Players/" . $p2 . ".yml", CONFIG::YAML);
                $a->set("$p1", "TRUE");
                $a->save();
                return;
            }
        }
	public function removeUserTEMP($p1, $p2) {
            if(isset($this->temp[$p1])) {
                if($this->temp[$p1] == $p2) {
                    unset($this->temp[$p1]);
                    return;
                }
            }
            return true;
	}
	public function removeLeaveTEMP($p1) {
            if(isset($this->temp[$p1])) {
                unset($this->temp[$p1]);
                return;
            }
	}
	public function getUserTEMP($p1, $p2) {
            if(isset($this->temp[$p1])) {
                if($this->temp[$p1] == $p2) {
                    return true;
                }
            }
	}
	public function areFriends($p1, $p2) {
            if($this->provider == "SQL") {
                $a = $this->db->query("SELECT * FROM friends WHERE p1='$p1' AND p2='$p2';");
                $b = $a->fetchArray(SQLITE3_ASSOC);
                return !empty($b);
            }
            if($this->provider == "YML") {
                $a = new Config($this->getDataFolder() . "Players/" . $p1 . ".yml", CONFIG::YAML);
                if($a->get("$p2")) {
                    return;
                } else {
                    return false;
                }
            }
	}
	public function hasFriends($p1) {
            if($this->provider == "SQL") {
                $a = $this->db->query("SELECT * FROM friends WHERE p1='$p1';");
                $b = $a->fetchArray(SQLITE3_ASSOC);
                return !empty($b);
            }
            if($this->getDataFolder() . "Players/" . $player . ".yml") {
                    return true;
            }else{
                    return false;
            }
	}
	public function onHurt(EntityDamageEvent $pf){
            if($pf instanceof EntityDamageByEntityEvent) {
                if($pf->getDamager() instanceof Player && $pf->getEntity() instanceof Player) {
                    $sender = $pf->getDamager();
                    $reciever = $pf->getEntity();
                    if($this->verify) {
                        $levelName = null;
                        $groupName = $this->pure->getUser($reciever)->getGroup($levelName)->getName();
                        $groupName2 = $this->pure->getUser($sender)->getGroup($levelName)->getName();
                        if($groupName == $groupName2 && $this->getConfig()->get("players-in-same-group-are-friendly")) {
                            $pf->setCancelled();
                        }
                    }
                    $friend1 = strtolower($pf->getEntity()->getName());
                    $friend2 = strtolower($pf->getDamager()->getName());
                    if($this->areFriends($friend1, $friend2) && $this->getConfig()->get("friends-are-friendly")) {
                        $pf->setCancelled(true);
                    }else{
                        return true;
                    }
                }
            }
	}
	public function onPlayerQuitEvent(PlayerQuitEvent $pf){
            $player = strtolower($pf->getPlayer()->getName());
            if($this->hasFriends($player)) {
                $this->removeLeaveTEMP($player);
            }else{
                return true;
            }
	}
}



