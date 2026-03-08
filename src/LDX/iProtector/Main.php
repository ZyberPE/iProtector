<?php

declare(strict_types=1);

namespace LDX\iProtector;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\player\Player;

use pocketmine\math\Vector3;
use pocketmine\world\Position;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\entity\EntityDamageEvent;

class Main extends PluginBase implements Listener{

	public array $areas = [];

	private array $selectingFirst = [];
	private array $selectingSecond = [];

	private array $firstPosition = [];
	private array $secondPosition = [];

	public function onEnable() : void{

		$this->getServer()->getPluginManager()->registerEvents($this,$this);

		@mkdir($this->getDataFolder());

		if(!file_exists($this->getDataFolder()."areas.json")){
			file_put_contents($this->getDataFolder()."areas.json","[]");
		}

		$data = json_decode(file_get_contents($this->getDataFolder()."areas.json"),true);

		foreach($data as $d){
			new Area(
				$d["name"],
				$d["flags"],
				new Vector3(...$d["pos1"]),
				new Vector3(...$d["pos2"]),
				$d["level"],
				$d["whitelist"],
				$this
			);
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{

		if(!$sender instanceof Player){
			$sender->sendMessage("Run this in-game.");
			return true;
		}

		if(!isset($args[0])){
			return false;
		}

		$p = strtolower($sender->getName());

		switch(strtolower($args[0])){

			case "pos1":
				$this->selectingFirst[$p] = true;
				$sender->sendMessage("Break/place block to set Pos1.");
			break;

			case "pos2":
				$this->selectingSecond[$p] = true;
				$sender->sendMessage("Break/place block to set Pos2.");
			break;

			case "create":

				if(!isset($args[1])) return true;

				if(!isset($this->firstPosition[$p],$this->secondPosition[$p])){
					$sender->sendMessage("Select pos1 and pos2 first.");
					return true;
				}

				$name = strtolower($args[1]);

				new Area(
					$name,
					["edit"=>true,"god"=>false,"touch"=>true],
					$this->firstPosition[$p],
					$this->secondPosition[$p],
					$sender->getWorld()->getFolderName(),
					[$p],
					$this
				);

				$this->saveAreas();

				unset($this->firstPosition[$p],$this->secondPosition[$p]);

				$sender->sendMessage("Area created.");
			break;

			case "list":

				foreach($this->areas as $a){
					$sender->sendMessage("- ".$a->getName());
				}

			break;

			case "delete":

				if(!isset($args[1])) return true;

				$name = strtolower($args[1]);

				if(isset($this->areas[$name])){
					$this->areas[$name]->delete();
					$sender->sendMessage("Area deleted.");
				}

			break;

			case "here":

				foreach($this->areas as $a){

					if($a->contains($sender->getPosition(),$sender->getWorld()->getFolderName())){
						$sender->sendMessage("Inside area ".$a->getName());
						return true;
					}
				}

				$sender->sendMessage("No area found.");
			break;

			case "tp":

				if(!isset($args[1])) return true;

				$name = strtolower($args[1]);

				if(!isset($this->areas[$name])){
					$sender->sendMessage("Area not found.");
					return true;
				}

				$a = $this->areas[$name];

				$sender->teleport(new Position(
					$a->getFirstPosition()->getX(),
					$a->getFirstPosition()->getY()+1,
					$a->getFirstPosition()->getZ(),
					$sender->getWorld()
				));

			break;

			case "flag":

				if(!isset($args[1],$args[2])) return true;

				$name = strtolower($args[1]);

				if(!isset($this->areas[$name])) return true;

				$area = $this->areas[$name];
				$flag = strtolower($args[2]);

				if(isset($args[3])){
					$mode = strtolower($args[3]) === "true";
				}else{
					$mode = !$area->getFlag($flag);
				}

				$area->setFlag($flag,$mode);

				$sender->sendMessage("Flag ".$flag." set to ".($mode?"true":"false"));

			break;

			case "whitelist":

				if(!isset($args[1],$args[2])) return true;

				$name = strtolower($args[1]);

				if(!isset($this->areas[$name])) return true;

				$area = $this->areas[$name];

				switch($args[2]){

					case "add":
						$area->setWhitelisted(strtolower($args[3]),true);
					break;

					case "remove":
						$area->setWhitelisted(strtolower($args[3]),false);
					break;

					case "list":
						foreach($area->getWhitelist() as $w){
							$sender->sendMessage($w);
						}
					break;
				}

			break;
		}

		return true;
	}

	public function saveAreas() : void{

		$data=[];

		foreach($this->areas as $a){

			$data[]=[
				"name"=>$a->getName(),
				"flags"=>$a->getFlags(),
				"pos1"=>[$a->getFirstPosition()->getX(),$a->getFirstPosition()->getY(),$a->getFirstPosition()->getZ()],
				"pos2"=>[$a->getSecondPosition()->getX(),$a->getSecondPosition()->getY(),$a->getSecondPosition()->getZ()],
				"level"=>$a->getWorldName(),
				"whitelist"=>$a->getWhitelist()
			];
		}

		file_put_contents($this->getDataFolder()."areas.json",json_encode($data,JSON_PRETTY_PRINT));
	}

	public function onPlace(BlockPlaceEvent $event) : void{

		$player=$event->getPlayer();
		$name=strtolower($player->getName());

		foreach($event->getTransaction()->getBlocks() as [$x,$y,$z,$block]){

			if(isset($this->selectingFirst[$name])){
				unset($this->selectingFirst[$name]);
				$this->firstPosition[$name]=new Vector3($x,$y,$z);
				$event->cancel();
				return;
			}

			if(isset($this->selectingSecond[$name])){
				unset($this->selectingSecond[$name]);
				$this->secondPosition[$name]=new Vector3($x,$y,$z);
				$event->cancel();
				return;
			}
		}
	}

	public function onBreak(BlockBreakEvent $event) : void{}

	public function onBucket(PlayerBucketEvent $event) : void{}

	public function onDamage(EntityDamageEvent $event) : void{}
}
