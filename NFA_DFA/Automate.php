<?php

/*
Ce projet est réalisé par:

Fouad Marzouki
Kawtar Nouara
Mehdi Lamrini

*/

use Nette\Utils\Strings;


class Automate extends Nette\Object
{
	protected $etats;
	protected $alphabet;
	protected $initials;
	protected $finals;
	const NFA = 'NFA',
		DFA = 'DFA',
		EPS = 'Epsilon',
		INIT_ST = '->',
		FINAL_ST = '*',
		NULL_POINTER = '-',
		etats_SEP = '|';



	public static function lireFichier($file)
	{
		$chemin = realpath($file);
		if (!$chemin) {
			throw new Exception("Fichier'$file' non trouvé");
		}

		$handle = fopen('safe://' . $chemin, 'r');
		if (!$handle) {
			throw new Exception("Le fichier'$file' ne peut pas être lu.");
		}

		$a = new static();
		$a->etats = array();

		$ligne = 1;
		$headingLoaded = FALSE;

		while (!feof($handle)) {

			$parts = Strings::trim( fgets($handle) );
			if (!strlen($parts)) { 
				$ligne++;
				continue;
			}

			$parts = Strings::split( $parts, '#[\s]+#' );

			if (!$headingLoaded) {

				
				$type = array_shift($parts);
				if ($type !== static::NFA && $type !== static::DFA) {
					throw new Exception("Unexpected '$type' in '$file:$ligne', expected '" . static::NFA . "' or '" . static::DFA . "'.");
				}

				
				$a->alphabet = $parts;
				if ( count( array_unique( $a->alphabet ) ) !== count( $a->alphabet ) ) {
					throw new Exception("Symboles dupliquées dans le fichier'$file:$ligne'.");
				}

				$headingLoaded = TRUE;
				$ligne++;
				continue;

			}

		
			$id = array_shift($parts);
			$init = $final = FALSE;

			$options = preg_quote(static::INIT_ST, '#') . '|' . preg_quote(static::FINAL_ST, '#');
			if ($m = Strings::match($id, "#^(?:($options)($options)?)#")) {
				array_shift($m);

				foreach ($m as $identifiant) {
					if ($identifiant === static::INIT_ST) {
						$init = TRUE;
						$id = substr($id, strlen($identifiant));

					} elseif ($identifiant === static::FINAL_ST) {
						$final = TRUE;
						$id = substr($id, strlen($identifiant));
					}
				}
			}

			if (!strlen($id)) {
				throw new Exception("Identifiant de l'état non spécifié dans '$file:$ligne'.");
			}

			if (!isset($etats[$id])) {
				$etats[$id] = new Etat($id);

			} elseif (count($etats[$id]->transitions)) {
				throw new Exception("Redéfinition de l'état $id' dans '$file:$ligne'.");
			}

			$transitions = array_combine($a->alphabet, $parts);
			if ($transitions === FALSE) {
				throw new Exception("Le nombre de transitions ne correspond pas au nomre de symboles dans'$file:$ligne'.");
			}

			foreach ($transitions as & $arrivees) {
				if ($arrivees === static::NULL_POINTER) {
					$arrivees = array();

				} else {
					$arrivees = explode(static::etats_SEP, $arrivees);
					sort($arrivees);
					$arrivees = array_values( array_unique( $arrivees ) );

					foreach ($arrivees as $symbole => & $s) {
						if (!isset($etats[$s])) {
							$s = new Etat($s);
							$etats[$s->id] = $s;

						} else {
							$s = $etats[$s];
						}
					}
				}
			}

			$etats[$id]->setTransitions( $transitions )
				->setInitial( $init )
				->setFinal( $final );

			$ligne++;

		}

		$a->updateetats( $etats );

		if (!$a->estDeteministe() && $type === static::DFA) {
			trigger_error("Automate marqué comme deterministe alors qui'l est detecte non-deterministe dans le fichier : '$file'.", E_USER_WARNING);
		}

		return $a;
	}







	public function plusdEpsilon()
	{
		if (($epsKey = array_search(static::EPS, $this->alphabet, TRUE)) === FALSE) {
			return $this;
		}

		foreach ($this->etats as $state) {
			$state->plusdEpsilon();
		}

		unset($this->alphabet[$epsKey]);
		$this->updateetats();

		return $this;
	}



	
	public function determiniser()
	{
		$this->plusdEpsilon();

		if ($this->estDeteministe()) {
			return $this;
		}

		$this->determEtats( $this->initials, $newetats );
		$this->updateetats( $newetats );

		return $this;
	}



	
	public function minimiser()
	{
		$this->determiniser();
		$this->plusdEtatsInaccessibles();

		$newetats = array(
			array(),
			array(),
		);

		$newTransitions = array();

		
		foreach ($this->etats as $id => $state) {
			$newetats[1][$id] = $state->final ? '1' : '2';
		}

		while ($newetats[0] !== $newetats[1]) {
			$newetats[0] = $newetats[1];
			$newetats[1] = array();

			
			foreach ($this->etats as $id => $state) {
				foreach ($state->transitions as $symbole => $target) {
					$newTransitions[$id][$symbole] = $newetats[0][ $target[0]->id ];
				}
			}

			
			foreach ($this->etats as $id => $state) {
			
				$found = FALSE;
				foreach ($this->etats as $i => $s) {
					if ($id === $i) break; 

					if ($newTransitions[$id] === $newTransitions[$i]
							&& $this->etats[$id]->final === $this->etats[$i]->final) {
						$found = $i;
						break;
					}
				}

				$newetats[1][$id] = $found === FALSE ? ( count($newetats[1]) ? (string) (max($newetats[1]) + 1) : '1' ) : (string) $newetats[1][$found];
			}
		}

		$etats = array();
		foreach ($newetats[1] as $oldID => $id) {
			if (!isset($etats[$id])) {
				$etats[$id] = new Etat($id);
			}

			if ( $this->etats[$oldID]->initial ) {
				$etats[$id]->setInitial( TRUE );
			}

			if ( $this->etats[$oldID]->final ) {
				$etats[$id]->setFinal( TRUE );
			}

			foreach ($newTransitions[$oldID] as $symbole => & $target) {
				if (!isset($etats[$target])) {
					$tmp = $etats[$target] = new Etat($target);
					$target = array($tmp);

				} else {
					$target = array($etats[$target]);
				}
			}

			$etats[$id]->setTransitions( $newTransitions[ $oldID ] );
		}

		$this->updateetats( $etats );
		return $this;
	}



	
	protected function removeState($id)
	{
		$id = (string) $id;

		if (!isset($this->etats[$id])) {
			throw new Exception("Impossible de supprimer l'état $id' - l'état n'existe pas.");
		}

		unset($this->etats[$id]);
		unset($this->initials[$id]);
		unset($this->finals[$id]);

		foreach ($this->etats as $state) {
			$state->removeStateById($id);
		}

		return $this;
	}



	public function estDeteministe()
	{
		if (count($this->initials) > 1) return FALSE;

		foreach ($this->etats as $state) {
			if (!$state->estDeteministe()) return FALSE;
		}

		return TRUE;
	}




	public function _print($forProgtest = FALSE)
	{ echo "<tr>";
		$espace = $forProgtest
			? function ($value = '') {
				return '  ';
			}
			: function ($value = '') {
				return strlen($value) < 8 ? "\t\t\t" : (strlen($value) < 16 ? "\t\t" : "\t");
			};

		$deterministic = $this->estDeteministe();

	
		echo "<td class='vocab'>";
		echo "Etat"
			. $espace();
			echo "</td>";

		foreach ($this->alphabet as $symbole) {
			echo "<td class='vocab'>";
			echo $symbole . $espace();
		echo "</td>";

		}
		echo "</tr>";

		

		
		foreach ($this->etats as $id => $state) {
			$state->_print($espace, $forProgtest);
		}

		echo "<br/>";
		return $this;
	}



	private function updateetats(array $newetats = NULL)
	{
		if ($newetats !== NULL) {
			$this->etats = $newetats;
		}

		uasort($this->etats, 'Etat::compare');
		$this->initials = $this->finals = array();

		foreach ($this->etats as $id => $state) {
			if ($state->initial) {
				$this->initials[$id] = $state;
			}

			if ($state->final) {
				$this->finals[$id] = $state;
			}
		}

		$this->valider();

		return $this;
	}




	public function valider()
	{
		if (!count($this->initials) || !count($this->finals)) {
			throw new Exception("Au moins un état final et un état initial.");
		}

		foreach ($this->etats as $state) {
			if (!count($state->transitions)) {
				throw new Exception("Définition de l'état $state' non trouvée.");
			}

			if ($state->alphabet !== $this->alphabet) {
				throw new Exception("Le symbole de l'état $state' ne correspond pas au vocabularire de l'automate.");
			}

			foreach ($state->transitions as $arrivees) {
				foreach ($arrivees as $p) {
					if (!isset($this->etats[$p->id])) {
						throw new Exception("Etat'$p' pointé par '$state' non trouvée.");
					}
				}
			}
		}

		return $this;
	}



	
	public function plusdEtatsInaccessibles()
	{
		$this->scanaccessible( $this->initials, $accessible );
		foreach (array_diff($this->etats, $accessible) as $state) {
			$this->removeState($state->id);
		}

		return $this;
	}



	
	private function scanaccessible(array $etats, & $accessible = NULL)
	{
		if ($accessible === NULL) {
			$accessible = array();
		}

		foreach ($etats as $state) {
			if (!in_array($state, $accessible, TRUE)) {
				$accessible[] = $state;

				foreach ($state->transitions as $symbole => $arrivees) {
					$this->scanaccessible($arrivees, $accessible);
				}
			}
		}
	}



	private function determEtats(array $etats, & $newetats = NULL, & $processed = NULL)
	{
		if ($newetats === NULL) {
			$newetats = array();
		}

		if ($processed === NULL) {
			$processed = array();
		}

		$id = $this->createId($etats);

		if (!isset($newetats[$id])) {
			$newetats[$id] = new Etat($id);
		}

		if (!count($newetats[$id]->transitions) && !in_array($id, $processed, TRUE)) {
			$processed[] = $id;
			$init = TRUE;
			$final = FALSE;
			$transitions = array();

			foreach ($this->alphabet as $symbole) {
				$closure = array();
				foreach ($etats as $state) {
					if ($init && !$state->initial) { 
						$init = FALSE;
					}

					if (!$final && $state->final) {
						$final = TRUE;
					}

					$closure = array_merge($closure, $state->transitions[$symbole]);
				}

				usort($closure, 'Etat::compare');
				$closure = array_unique($closure);

				$newID = $this->createId($closure);
				if (!isset($newetats[$newID])) {
					$newetats[$newID] = new Etat($newID);
				}

				$transitions[$symbole] = array($newetats[$newID]);

				if ($newID !== $id) {
					$this->determEtats( $closure, $newetats, $processed );
				}
			}

			$newetats[$id]
					->setInitial(count($etats) && $init)
					->setFinal($final)
					->setTransitions($transitions);
		}
	}



	
	private function createId(array $etats)
	{
		return '{' . implode(',', $etats) . '}';
	}



	
	
}