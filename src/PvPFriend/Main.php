<?php
/**
 * author: LilCrispy2o9/Angelo Vidrio
 */
namespace PvPFriend;
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
use pocketmine\utils\TextFormat;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
class Main extends PluginBase  implements Listener {
	

    public function onEnable(){
		@mkdir($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->config = new Config($this->getDataFolder() . "config.yml", CONFIG::YAML, array(
			"players-with-same-perms-are-friendly" => true,
		));
		if(!$this->getServer()->getPluginManager()->getPlugin("PurePerms") == true) {
			$this->getLogger()->info( TextFormat::RED . "PurePerms Not Loaded With PvPFriend!" );
		}else{
			$this->pure = $this->getServer()->getPluginManager()->getPlugin("PurePerms");
			$this->getLogger()->info( TextFormat::GREEN . "PurePerms Loaded With PvPFriend!" );
		}
		$this->getLogger()->info( TextFormat::GREEN . "PvPFriendly Loaded!" );
    }
	public function onEntityDamageByEntityEvent(EntityDamageEvent $pf){
		$reciever = $pf->getEntity()->getPlayer();
		$sender = $pf->getDamager()->getPlayer();
		$levelName = null;
		$groupName = $this->pure->getUser($reciever)->getGroup($levelName)->getName();
		$groupName2 = $this->pure->getUser($sender)->getGroup($levelName)->getName();
		if($groupName == $groupName2) {
			$pf->setCancelled(true);
		}else{
			$pf->setCancelled(false);
		}
	}
}


