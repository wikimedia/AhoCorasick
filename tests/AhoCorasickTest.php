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
 * @covers \AhoCorasick\MultiStringMatcher
 */
class AhoCorasickTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Sort results of NaiveMultiStringMatcher or MultiStringMatcher.
	 *
	 * Helps us assert equivalence.
	 *
	 * @param array &$matches Results array.
	 */
	public function sortMatcherResults( &$matches ) {
		// Sort the results by match offset, then by match length,
		// then by search keyword.
		usort( $matches, static function ( $a, $b ) {
			return ( $a[0] - $b[0] )
				?: ( strlen( $a[1] ) - strlen( $b[1] ) )
				?: strcmp( $a[1], $b[1] );
		} );
	}

	public static function matcherCaseProvider() {
		$testCases = [
			[
				'She sells sea shells by the sea shore.',
				[ 's', 'se', 'sea', 'ore', 'hell', 'eat' ]
			],
			[
				'She sells sea shells by the sea shore.',
				[ 's', 'ls', 'lls', 'hells', 'shell', 'she', 'he', 'h' ],
			],
			[
				'井の中の蛙大海を知らず。',
				[ 'の', '', '食', '小蓑' ],
			],
			[
				'Вдохновение — это умение приводить '
					. 'себя в рабочее состояние.',
				[ 'это умение приводить себя' ],
			],
			[
				"初しぐれ猿も小蓑をほしげ也\nはつしぐれさるもこみのをほしげなり",
				[ "しげ也\nはつし" ],
			],
			[
				" (╯°□°）╯︵ ┻━┻  ",
				[ '°□°' ],
			],
			[
				'',
				[ 'a' ],
			],
			[
				'xxxyyy999',
				[ '999' ]
			],
		];
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

	/**
	 * @covers \AhoCorasick\MultiStringMatcher::__construct
	 */
	public function testConstructEmpty() {
		try {
			$warningEmitted = false;
			set_error_handler( static function () use ( &$warningEmitted ) {
				$warningEmitted = true;
			}, E_USER_WARNING );

			new MultiStringMatcher( [] );

			$this->assertTrue( $warningEmitted, 'No PHP warning was emitted.' );
		} finally {
			restore_error_handler();
		}
	}

	/** @covers \AhoCorasick\MultiStringMatcher::getKeywords */
	public function testGetKeywords() {
		$searchKeywords = [ 's', 'sea', 'の' ];
		$matcher = new MultiStringMatcher( $searchKeywords );

		$this->assertEquals( $searchKeywords, $matcher->getKeywords() );
	}

	public static function replacerCaseProvider() {
		return [
			[
				'The quick brown fox jumps over the lazy dog.',
				[ 'brown' => 'orange', 'brown fox' => 'blue cat', 'brown fox jx' => 'x' ]
			],
			[
				"It's raining snakes and ladders here",
				[ "It's" => 'It is', 'snake' => 'cat', 'ladder' => 'dog', 'here' => 'out there' ],
			],
			[
				'Now is the time for all good men to come to the aid of the party',
				[ 'USA' => 'United States' ],
			],
			[
				"富士の風や扇にのせて江戸土産\n" .
					"ふじのかぜやおうぎにのせてえどみやげ",
				[ '江戸' => '東京' ],
			],
		];
	}

	/**
	 * @dataProvider replacerCaseProvider
	 * @covers \AhoCorasick\MultiStringReplacer
	 */
	public function testMultiStringReplacer( $inputText, $replacePairs ) {
		$replacer = new MultiStringReplacer( $replacePairs );
		$actual = $replacer->searchAndReplace( $inputText );
		$expected = strtr( $inputText, $replacePairs );
		$this->assertEquals( $expected, $actual );
	}
}
