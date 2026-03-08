<?php

declare(strict_types=1);

namespace LDX\iProtector;

use pocketmine\world\World;
use pocketmine\math\Vector3;

class Area{

	private array $flags;
	private string $name;
	private Vector3 $pos1;
	private Vector3 $pos2;
	private string $worldname;
	private array $whitelist;
	private Main $plugin;

	public function __construct(
		string $name,
		array $flags,
		Vector3 $pos1,
		Vector3 $pos2,
		string $worldname,
		array $whitelist,
		Main $plugin
	){
		$this->name = strtolower($name);
		$this->flags = $flags;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->worldname = $worldname;
		$this->whitelist = $whitelist;
		$this->plugin = $plugin;

		$this->save();
	}

	public function getName() : string{
		return $this->name;
	}

	public function getFirstPosition() : Vector3{
		return $this->pos1;
	}

	public function getSecondPosition() : Vector3{
		return $this->pos2;
	}

	public function getFlags() : array{
		return $this->flags;
	}

	public function getFlag(string $flag) : bool{
		return $this->flags[$flag] ?? false;
	}

	public function setFlag(string $flag, bool $value) : bool{
		if(isset($this->flags[$flag])){
			$this->flags[$flag] = $value;
			$this->plugin->saveAreas();
			return true;
		}

		return false;
	}

	public function toggleFlag(string $flag) : bool{
		if(isset($this->flags[$flag])){
			$this->flags[$flag] = !$this->flags[$flag];
			$this->plugin->saveAreas();
			return $this->flags[$flag];
		}

		return false;
	}

	public function contains(Vector3 $pos, string $worldname) : bool{
		return (
			min($this->pos1->getX(), $this->pos2->getX()) <= $pos->getX() &&
			max($this->pos1->getX(), $this->pos2->getX()) >= $pos->getX() &&
			min($this->pos1->getY(), $this->pos2->getY()) <= $pos->getY() &&
			max($this->pos1->getY(), $this->pos2->getY()) >= $pos->getY() &&
			min($this->pos1->getZ(), $this->pos2->getZ()) <= $pos->getZ() &&
			max($this->pos1->getZ(), $this->pos2->getZ()) >= $pos->getZ() &&
			$this->worldname === $worldname
		);
	}

	public function getWorldName() : string{
		return $this->worldname;
	}

	public function getWorld() : ?World{
		return $this->plugin->getServer()->getWorldManager()->getWorldByName($this->worldname);
	}

	public function isWhitelisted(string $playerName) : bool{
		return in_array($playerName, $this->whitelist, true);
	}

	public function setWhitelisted(string $name, bool $value = true) : bool{
		if($value){
			if(!in_array($name, $this->whitelist, true)){
				$this->whitelist[] = $name;
				$this->plugin->saveAreas();
				return true;
			}
		}else{
			$key = array_search($name, $this->whitelist, true);
			if($key !== false){
				unset($this->whitelist[$key]);
				$this->whitelist = array_values($this->whitelist);
				$this->plugin->saveAreas();
				return true;
			}
		}

		return false;
	}

	public function getWhitelist() : array{
		return $this->whitelist;
	}

	public function delete() : void{
		unset($this->plugin->areas[$this->getName()]);
		$this->plugin->saveAreas();
	}

	public function save() : void{
		$this->plugin->areas[$this->name] = $this;
	}
}
