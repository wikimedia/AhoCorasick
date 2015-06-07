<?php
/**
 * AhoCorasick PHP Library
 *
 * A PHP implementation of the Aho-Corasick string matching algorithm.
 *
 * Alfred V. Aho and Margaret J. Corasick, "Efficient string matching:
 *  an aid to bibliographic search", CACM, 18(6):333-340, June 1975.
 *
 * @link http://xlinux.nist.gov/dads//HTML/ahoCorasick.html
 * @link https://en.wikipedia.org/wiki/Aho-Corasick_string_matching_algorithm
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

/**
 * Represents a finite state machine that can find all occurrences
 * of a set of search keywords in a body of text.
 *
 * The time it takes to construct the finite state machine is
 * proportional to the sum of the lengths of the search keywords.
 * Once constructed, the machine can locate all occurences of all
 * search keywords in a body of text in a single pass, making exactly
 * one state transition per input character.
 *
 * This is an implementation of the Aho-Corasick string matching
 * algorithm.
 *
 * Alfred V. Aho and Margaret J. Corasick, "Efficient string matching:
 *  an aid to bibliographic search", CACM, 18(6):333-340, June 1975.
 *
 * @link http://xlinux.nist.gov/dads//HTML/ahoCorasick.html
 *
 * @param string $text The input string.
 * @param array $keywords An array of strings to search for.
 * @return array[] An array of (offset, substring) arrays.
 */
class MultiStringMatcher {

	/** @var string[] The set of keywords to be searched for. **/
	protected $searchKeywords = array();

	/** @var array The set of unique characters that appear in the search keywords. **/
	protected $searchChars = array();

	/** @var int The number of possible states of the string-matching finite state machine. **/
	protected $numStates = 1;

	/** @var array Mapping of states to outputs. **/
	protected $outputs = array();

	protected $noTransitions = array();

	protected $yesTransitions = array();


	/**
	 * Constructor.
	 *
	 * @param string[] $searchKeywords The set of keywords to be matched.
	 */
	public function __construct( array $searchKeywords ) {
		foreach ( $searchKeywords as $keyword ) {
			if ( $keyword !== '' && !in_array( $keyword, $this->searchKeywords ) ) {
				$this->searchKeywords[] = $keyword;
			}
		}

		if ( !$this->searchKeywords ) {
			trigger_error( __METHOD__ . ': The set of search keywords is empty.', E_USER_WARNING );
			return;
		}

		$this->computeSuccessTransitions();
		$this->computeFailTransitions();
	}


	/**
	 * Accessor for the search keywords.
	 *
	 * @return string[] Search keywords.
	 */
	public function getKeywords() {
		return $this->searchKeywords;
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
	public function nextState( $currentState, $inputChar ) {
		while (
			$currentState !== 0 &&
			!isset( $this->yesTransitions[$currentState][$inputChar] )
		) {
			$currentState = $this->noTransitions[$currentState];
		}
		return isset( $this->yesTransitions[$currentState][$inputChar] ) ?
			$this->yesTransitions[$currentState][$inputChar] : 0;
	}


	/**
	 * Locate the search keywords in some text.
	 *
	 * @param string $text The string to search in.
	 * @return array[] An array of matches. Each match is a vector
	 *  containing an integer offset and the matched keyword.
	 *
	 * @par Example:
	 * @code
	 *   $keywords = new MultiStringMatcher( array( 'ore', 'hell' ) );
	 *   $keywords->searchIn( 'She sells sea shells by the sea shore.' );
	 *   // result: array( array( 15, 'hell' ), array( 34, 'ore' ) )
	 * @endcode
	 */
	public function searchIn( $text ) {
		if ( !$this->searchKeywords || $text === '' ) {
			return array();  // fast path
		}

		$state = 0;
		$results = array();
		$length = mb_strlen( $text );

		for ( $i = 0; $i < $length; $i++ ) {
			$ch = mb_substr( $text, $i, 1 );
			$state = $this->nextState( $state, $ch );
			if ( !empty( $this->outputs[$state] ) ) {
				foreach ( $this->outputs[$state] as $match ) {
					$offset = $i - mb_strlen( $match ) + 1;
					$results[] = array( $offset, $match );
				}
			}
		}

		return $results;
	}


	/**
	 * Get the state transitions which the string-matching automaton
	 * shall make as it advances through input text.
	 *
	 * Constructs a directed tree with a root node which represents the
	 * initial state of the string-matching automaton and from which a
	 * path exists which spells out each search keyword.
	 *
	 * @return array[]
	 */
	protected function computeSuccessTransitions() {
		foreach ( $this->searchKeywords as $keyword ) {
			$state = 0;
			$length = mb_strlen( $keyword );

			for ( $i = 0; $i < $length; $i++ ) {
				$ch = mb_substr( $keyword, $i, 1 );
				if ( !in_array( $ch, $this->searchChars ) ) {
					$this->searchChars[] = $ch;
				}
				if ( !empty( $this->yesTransitions[$state][$ch] ) ) {
					$state = $this->yesTransitions[$state][$ch];
				} else {
					$this->yesTransitions[$state][$ch] = $this->numStates;
					$state = $this->numStates++;
				}
			}

			$this->outputs[$state][] = $keyword;
		}
	}


	/**
	 * Get the state transitions which the string-matching automaton
	 * shall make when a partial match proves false.
	 *
	 * @param array[] $this->yesTransitions The array created by
	 *  MultiStringMatcher::computeSuccessTransitions.
	 * @return array[]
	 */
	protected function computeFailTransitions() {
		$queue = array();
		foreach ( $this->yesTransitions[0] as $ch => $toState ) {
			if ( $toState !== 0 ) {
				$queue[] = $toState;
				$this->noTransitions[$toState] = 0;
			}
		}

		while ( ( $r = array_shift( $queue ) ) !== null ) {
			if ( empty( $this->yesTransitions[$r] ) ) {
				continue;
			}
			foreach ( $this->yesTransitions[$r] as $ch => $toState ) {
				$queue[] = $toState;
				$state = $this->noTransitions[$r];

				while ( $state !== 0 && empty( $this->yesTransitions[$state][$ch] ) ) {
					$state = $this->noTransitions[$state];
				}

				$failState = isset( $this->yesTransitions[$state][$ch] ) ?
					$this->yesTransitions[$state][$ch] : 0;
				$this->noTransitions[$toState] = $failState;
				if ( isset( $this->outputs[$failState] ) ) {
					$this->outputs[$toState] = empty( $this->outputs[$toState] )
						? $this->outputs[$failState]
						: array_merge( $this->outputs[$toState], $this->outputs[$failState] );
				}
			}
		}

		return $this->noTransitions;
	}
}
