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

/**
 * A multiple-substring search using a naive algorithm.
 * (Iterate through the entire body of input text for each search keyword.)
 */
class NaiveMultiStringMatcher extends MultiStringMatcher {

	/**
	 * @param string $text The text to search in.
	 * @return array
	 */
	public function searchIn( $text ) {
		$matches = [];
		foreach ( $this->searchKeywords as $keyword => $length ) {
			$keyword = strval( $keyword );
			$offset = 0;
			while ( true ) {
				$offset = strpos( $text, $keyword, $offset );
				if ( $offset === false ) {
					break;
				}
				$matches[] = [ $offset, $keyword ];
				$offset = $offset + $length;
			}
		}
		return $matches;
	}
}
