[![Packagist.org](https://img.shields.io/packagist/v/wikimedia/aho-corasick.svg?style=flat)](https://packagist.org/packages/wikimedia/aho-corasick)

AhoCorasick
===========

AhoCorasick is a PHP implementation of the [Aho-Corasick][1] string search
algorithm, which is an efficient way of searching a body of text for multiple
search keywords.

Here is how you use it:

```php
use AhoCorasick\MultiStringMatcher;

$keywords = new MultiStringMatcher( array( 'ore', 'hell' ) );

$keywords->searchIn( 'She sells sea shells by the sea shore.' );
// Result: array( array( 15, 'hell' ), array( 34, 'ore' ) )

$keywords->searchIn( 'Say hello to more text. MultiStringMatcher objects are reusable!' );
// Result: array( array( 4, 'hell' ), array( 14, 'ore' ) )
```


Features
--------

The algorithm works by constructing a finite-state machine out of the set of
search keywords. The time it takes to construct the finite state machine is
proportional to the sum of the lengths of the search keywords. Once
constructed, the machine can locate all occurences of all search keywords in
any body of text in a single pass, making exactly one state transition per
input character.

The algorithm originates from ["Efficient string matching: an aid to bibliographic search"][paper] (CACM, Volume 18, Issue 6, June 1975) by Alfred V. Aho and Margaret J. Corasick.

See also the definition and reference implementation on [nist.gov][dads].


[paper]: https://doi.org/10.1145/360825.36085
[dads]: http://xlinux.nist.gov/dads/HTML/ahoCorasick.html

Contribute
----------

- Issue tracker: https://phabricator.wikimedia.org/tag/ahocorasick/
- Source code: https://gerrit.wikimedia.org/g/AhoCorasick


Support
-------

If you are having issues, [please let us know][2].


License
-------

The project is licensed under the Apache license.


[1]: https://en.wikipedia.org/wiki/Aho%E2%80%93Corasick_string_matching_algorithm
[2]: https://phabricator.wikimedia.org/maniphest/task/create/?projects=PHID-PROJ-hs5ausnvlfs4e3n5gmzg
