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

namespace AhoCorasick\Test;

use AhoCorasick\MultiStringMatcher;
use AhoCorasick\MultiStringReplacer;

/**
 * A multiple-substring search using a naive algorithm.
 * (Iterate through the entire body of input text for each search keyword.)
 */
class NaiveMultiStringMatcher extends MultiStringMatcher {

	/** @param string $text The text to search in. */
	public function searchIn( $text ) {
		$matches = array();
		foreach ( $this->searchKeywords as $keyword => $length ) {
			$offset = 0;
			while ( true ) {
				$offset = strpos( $text, $keyword, $offset );
				if ( $offset === false ) {
					break;
				}
				$matches[] = array( $offset, $keyword );
				$offset = $offset + $length;
			}
		}
		return $matches;
	}
}

/**
 * @covers AhoCorasick\MultiStringMatcher
 */
class AhoCorasickTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Sort results of NaiveMultiStringMatcher or MultiStringMatcher.
	 *
	 * Helps us assert equivalence.
	 *
	 * @param &$matches array Results array.
	 */
	public function sortMatcherResults( &$matches ) {
		// Sort the results by match offset, then by match length,
		// then by search keyword.
		usort( $matches, function ( $a, $b ) {
			return ( $a[0] - $b[0] )
				?: ( strlen( $a[1] ) - strlen( $b[1] ) )
				?: strcmp( $a[1], $b[1] );
		} );
	}

	public function matcherCaseProvider() {
		$testCases = array(
			array(
				'She sells sea shells by the sea shore.',
				array( 's', 'se', 'sea', 'ore', 'hell', 'eat' )
			),
			array(
				'She sells sea shells by the sea shore.',
				array( 's', 'ls', 'lls', 'hells', 'shell', 'she', 'he', 'h' ),
			),
			array(
				'井の中の蛙大海を知らず。',
				array( 'の', '', '食', '小蓑' ),
			),
			array(
				'Вдохновение — это умение приводить '
					. 'себя в рабочее состояние.',
				array( 'это умение приводить себя' ),
			),
			array(
				"初しぐれ猿も小蓑をほしげ也\nはつしぐれさるもこみのをほしげなり",
				array( "しげ也\nはつし" ),
			),
			array(
				" (╯°□°）╯︵ ┻━┻  ",
				array( '°□°' ),
			),
			array(
				'',
				array( 'a' ),
			),
		);

		return $testCases;
	}


	/** @dataProvider matcherCaseProvider */
	public function testMultiStringMatcher( $inputText, $searchKeywords ) {
		$referenceMatcher = new NaiveMultiStringMatcher( $searchKeywords );
		$referenceResults = $referenceMatcher->searchIn( $inputText );
		$this->sortMatcherResults( $referenceResults );

		$actualMatcher = new MultiStringMatcher( $searchKeywords );
		$actualResults = $actualMatcher->searchIn( $inputText );
		$this->sortMatcherResults( $actualResults );

		$this->assertEquals( $referenceResults, $actualResults );
	}


	public function replacerCaseProvider() {
		return array(
			array(
				'The quick brown fox jumps over the lazy dog.',
				array( 'brown' => 'orange', 'brown fox' => 'blue cat', 'brown fox jx' => 'x' )
			),
			array(
				"It's raining snakes and ladders here",
				array( "It's" => 'It is', 'snake' => 'cat', 'ladder' => 'dog', 'here' => 'out there' ),
			),
			array(
				'Now is the time for all good men to come to the aid of the party',
				array( 'USA' => 'United States' ),
			),
			array(
				"富士の風や扇にのせて江戸土産\n" .
					"ふじのかぜやおうぎにのせてえどみやげ",
				array( '江戸' => '東京' ),
			),
		);
	}


	/** @dataProvider replacerCaseProvider */
	public function testMultiStringReplacer( $inputText, $replacePairs ) {
		$replacer = new MultiStringReplacer( $replacePairs );
		$actual = $replacer->searchAndReplace( $inputText );
		$expected = strtr( $inputText, $replacePairs );
		$this->assertEquals( $expected, $actual );
	}
}
