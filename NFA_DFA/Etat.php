<?php

/*
Ce projet est réalisé par:

Fouad Marzouki
Kawtar Nouara
Mehdi Lamrini
*/
class Etat extends Nette\Object
{

	protected $id;
	protected $initial = FALSE;
	protected $final = FALSE;
	protected $transitions = array();
	protected $closure = array();


	public function __construct($id)
	{
		$this->id = (string) $id;
	}


	public function __toString()
	{
		return $this->id;
	}



	
	public function _print($espace, $forProgtest = FALSE)
	{ 
		echo "<tr>";
		foreach ($this->closure as $cle ) {
			echo $cle;
			
		}

		if ($this->initial){
			if ($this->final){
				echo "<td >";
				echo "<span class='finitial'>".($prefix = Automate::INIT_ST . Automate::FINAL_ST)."</span>"; }
			else{
				echo "<td>";
				echo "<span class='finitial'>".($prefix = ' ' . Automate::INIT_ST)."</span>" ;
			}
		}
		elseif ($this->final)
		{
			echo "<td>";
			echo "<span class='finitial'>".($prefix = ' ' . Automate::FINAL_ST)."</span>";
			
		}
		else{
			echo "<td>";
			echo ($prefix = '  ');
			
		}
		
		echo $this->id . $espace( $prefix . $this->id );
		echo "</td>";

		
		foreach ($this->transitions as $arrivees) {
			echo "<td>";
			echo ($value = (count($arrivees)
					? implode( Automate::etats_SEP, $arrivees )
					: Automate::NULL_POINTER ) )
				. $espace( $value );
			echo "</td>";
		}
		echo "</tr>";
		
	}



	
	public function getId()
	{
		return $this->id;
	}

	
	public function setID($id)
	{
		$this->id = (string) $id;
		return $this;
	}


	public function getInitial()
	{
		return $this->initial;
	}



	
	public function setInitial($i = TRUE)
	{
		$this->initial = $i;
		return $this;
	}

	
	public function getFinal()
	{
		return $this->final;
	}


	public function setFinal($f = TRUE)
	{
		$this->final = $f;
		return $this;
	}

	
	public function getTransitions()
	{
		return $this->transitions;
	}

	
	public function setTransitions(array $t)
	{
		$this->transitions = $t;
		return $this;
	}


	public function getAlphabet()
	{
		return array_keys($this->transitions);
	}


	public function plusdEpsilon()
	{
		$epsKey = Automate::EPS;
		$this->epsilonclosure($this, $closure);

		if (count($closure)) {
			$transitions = array();

			foreach ($closure as $etat) {
				if (!$this->final && $etat->final) {
					$this->final = TRUE;
				}

				foreach ($this->getAlphabet() as $symbole) {
					if ($symbole === $epsKey) continue;

					if (!isset($transitions[$symbole])) {
						$transitions[$symbole] = array();
					}

					$transitions[$symbole] = array_merge($transitions[$symbole], $etat->transitions[$symbole]);
					usort($transitions[$symbole], __CLASS__ . '::compare');
					$transitions[$symbole] = array_unique( $transitions[$symbole] );
				}
			}

			$this->transitions = $transitions;

		} else {
			unset($this->transitions[ $epsKey ]);
		}
		$this->closure = $closure;
		return $this;
	}


	
	private function epsilonclosure($etat, & $closure = NULL)
	{
		$epsKey = Automate::EPS;
		if ($closure === NULL) {
			$closure = array();
		}

		if (isset($etat->transitions[$epsKey]) && count($etat->transitions[$epsKey])) {
			if (!in_array($etat, $closure, TRUE)) {
				$closure[] = $etat;
			}

			foreach ($etat->transitions[$epsKey] as $s) {
				if (!in_array($s, $closure, TRUE) ) {
					$closure[] = $s;
					$this->epsilonclosure($s, $closure);
				}
			}
		}
	}




	public function isBlind()
	{
		if ($this->final) return FALSE;

		foreach ($this->transitions as $arrivees) {
			if (count($arrivees) > 1 || (count($arrivees) === 1 && $arrivees[0] !== $this))
				return FALSE;
		}

		return TRUE;
	}



	public function estDeteministe()
	{
		foreach ($this->transitions as $symbole => $arrivees) {
			if ($symbole === Automate::EPS || count($arrivees) !== 1) return FALSE;
		}

		return TRUE;
	}




	public function enleverEtat($id)
	{
		foreach ($this->transitions as $symbole => $arrivees) {
			foreach ($arrivees as $cle => $etat) {
				if ($etat->id === $id && $etat->id !== $this->id) {
					unset($arrivees[$cle]);
				}
			}

			$this->transitions[$symbole] = array_values($arrivees);
		}

		return $this;
	}




	public function removeTransition($symbole, Etat $etat)
	{
		if (($cle = array_search($etat, (array) $this->transitions[$symbole], TRUE)) === FALSE) {
			throw new Exception("L'état '$etat' non trouve dans la transition par le symbole'$symbole' pour l'état '$this' ");
		}

		unset($this->transitions[$symbole][$cle]);
		return $this;
	}



	public static function compare(Etat $s1, Etat $s2)
	{
		if (is_numeric($s1->id) && is_numeric($s2->id)) {
			return (double) $s1->id - (double) $s2->id;

		} else {
			$cmp = strcmp($s1->id, $s2->id);

			if ($cmp && ($diff = strlen($s1->id) - strlen($s2->id) > 0)) {
				return $diff;
			}

			return $cmp;
		}
	}
}