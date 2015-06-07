<?php
/**
 * AhoCorasick PHP Library
 *
 * Copyright (C) 2015 Ori Livneh <ori@wikimedia.org>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * @file
 * @author Ori Livneh <ori@wikimedia.org>
 */

namespace AhoCorasick;

use AhoCorasick\MultiStringMatcher;

/**
 * Represents a variant of the Aho-Corasick string matching algorithm
 * which uses a deterministic finite automaton.
 *
 * The time it takes to construct the finite state machine is
 * proportional to the sum of the lengths of the search keywords.
 * Once constructed, the machine can locate all occurences of all
 * search keywords in a body of text in a single pass, making exactly
 * one state transition per input character.
 */
class DeterministicMultiStringMatcher extends MultiStringMatcher {

	/** @var array[] Mapping of all possible state transitions. **/
	protected $stateMachine = null;

	/**
	 * Constructor.
	 *
	 * @param string[] $searchKeywords The set of keywords to be matched.
	 */
	public function __construct( array $searchKeywords ) {
		parent::__construct( $searchKeywords );
		$this->computeFiniteStateMachine();
	}


	/**
	 * Map the current state and input character to the next state.
	 *
	 * @param int $currentState The current state of the string-matching
	 *  automaton.
	 * @param string $inputChar The character the string-matching
	 *  automaton is currently processing.
	 * @return int The state the automaton should transition to.
	 */
	public function nextState( $state, $ch ) {
		return isset( $this->stateMachine[$state][$ch] ) ?
			$this->stateMachine[$state][$ch] : 0;
	}

	/**
	 * Construct the string-matching finite state machine.
	 *
	 * The machine will make one state transition per input symbol.
	 */
	protected function computeFiniteStateMachine() {
		for ( $r = 0; $r < $this->numStates; $r++ ) {
			foreach ( $this->searchChars as $ch ) {
				$state = $r;
				while ( $state !== 0 && !isset( $this->yesTransitions[$state][$ch] ) ) {
					$state = $this->noTransitions[$state];
				}
				if ( isset( $this->yesTransitions[$state][$ch] ) ) {
					$this->stateMachine[$r][$ch] = $this->yesTransitions[$state][$ch];
				} else {
					$this->stateMachine[$r][$ch] = isset( $this->noTransitions[$state] )
						? $this->noTransitions[$state] : 0;
				}
			}
		}
	}
}
