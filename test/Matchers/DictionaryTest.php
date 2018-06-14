<?php

namespace ZxcvbnPhp\Test\Matchers;

use ZxcvbnPhp\Matchers\DictionaryMatch;
use ZxcvbnPhp\Matchers\ReverseDictionaryMatch;

class DictionaryTest extends AbstractMatchTest
{
    protected static $testDicts = array(
        'd1' => array(
            'motherboard' => 1,
            'mother' => 2,
            'board' => 3,
            'abcd' => 4,
            'cdef' => 5,
        ),
        'd2' => array(
            'z' => 1,
            '8' => 2,
            '99' => 3,
            '$' => 4,
            'asdf1234&*' => 5
        )
    );

    public function madeUpWordsProvider()
    {
        return array(
            array('jjj'),
            array('kdncpqw'),
        );
    }

    /**
     * @dataProvider madeUpWordsProvider
     * @param string $password
     */
    public function testWordsNotInDictionary($password)
    {
        $matches = DictionaryMatch::match($password);
        $this->assertEmpty($matches, "does not match non-dictionary words");
    }

    public function testContainingWords()
    {
        $password = 'motherboard';
        $patterns = ['mother', 'motherboard', 'board'];

        $this->checkMatches(
            "matches words that contain other words",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 5], [0, 10], [6, 10]],
            [
                'matchedWord' => $patterns,
                'rank' => [2, 1, 3],
                'dictionaryName' => ['d1', 'd1', 'd1'],
            ]
        );
    }

    public function testOverlappingWords()
    {
        $password = 'abcdef';
        $patterns = ['abcd', 'cdef'];

        $this->checkMatches(
            "matches multiple words when they overlap",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 3], [2, 5]],
            [
                'matchedWord' => $patterns,
                'rank' => [4, 5],
                'dictionaryName' => ['d1', 'd1', 'd1'],
            ]
        );
    }

    public function testUppercasingIgnored()
    {
        $password = 'BoaRdZ';
        $patterns = ['BoaRd', 'Z'];

        $this->checkMatches(
            "ignores uppercasing",
            DictionaryMatch::match($password, [], self::$testDicts),
            'dictionary',
            $patterns,
            [[0, 4], [5, 5]],
            [
                'matchedWord' => ['board', 'z'],
                'rank' => [3, 1],
                'dictionaryName' => ['d1', 'd2'],
            ]
        );
    }

    public function testWordsSurroundedByNonWords()
    {
        $prefixes = array('q', '%%');
        $suffixes = array('%', 'qq');
        $pattern = 'asdf1234&*';

        foreach ($this->generatePasswords($pattern, $prefixes, $suffixes) as list($password, $i, $j)) {
            $this->checkMatches(
                "identifies words surrounded by non-words",
                DictionaryMatch::match($password, [], self::$testDicts),
                'dictionary',
                [$pattern],
                [[$i, $j]],
                [
                    'matchedWord' => [$pattern],
                    'rank' => [5],
                    'dictionaryName' => ['d2'],
                ]
            );
        }
    }

    public function testAllDictionaryWords()
    {
        foreach (self::$testDicts as $dictionaryName => $dict) {
            foreach ($dict as $word => $rank) {
                if ($word === 'motherboard') {
                    continue; // skip words that contain others
                }

                $this->checkMatches(
                    "matches against all words in provided dictionaries",
                    DictionaryMatch::match($word, [], self::$testDicts),
                    'dictionary',
                    [$word],
                    [[0, strlen($word) - 1]],
                    [
                        'matchedWord' => [$word],
                        'rank' => [$rank],
                        'dictionaryName' => [$dictionaryName],
                    ]
                );
            }
        }
    }

    public function testDefaultDictionary()
    {
        $password = 'wow';
        $patterns = [$password];

        $this->checkMatches(
            "default dictionaries",
            DictionaryMatch::match($password),
            'dictionary',
            $patterns,
            [[0, 2]],
            [
                'matchedWord' => $patterns,
                'rank' => [322],
                'dictionaryName' => ['us_tv_and_film'],
            ]
        );
    }

    public function testUserProvidedInput()
    {
        $password = 'foobar';
        $patterns = ['foo', 'bar'];

        $matches = DictionaryMatch::match($password, ['foo', 'bar']);
        $matches = array_values(array_filter($matches, function ($match) {
            return $match->dictionaryName === 'user_inputs';
        }));

        $this->checkMatches(
            "matches with provided user input dictionary",
            $matches,
            'dictionary',
            $patterns,
            [[0, 2], [3, 5]],
            [
                'matchedWord' => ['foo', 'bar'],
                'rank' => [1, 2],
            ]
        );
    }

    public function testMatch()
    {
        $password = 'password';
        $matches = DictionaryMatch::match($password);
        // 11 matches for "password" in english and password dictionaries.
        $this->assertCount(11, $matches);
        $this->assertSame('pass', $matches[0]->token, "Token incorrect");
        $this->assertSame('passwords', $matches[0]->dictionaryName, "Dictionary name incorrect");

        $password = '8dll20BEN3lld0';
        $matches = DictionaryMatch::match($password);
        $this->assertCount(2, $matches);

        $password = '39Kx9.1x0!3n6';
        $matches = DictionaryMatch::match($password, array($password));
        $this->assertCount(1, $matches);
        $this->assertSame('user_inputs', $matches[0]->dictionaryName, "Dictionary name incorrect");
  }

    public function testEntropy()
    {
        $password = 'password';
        $matches = DictionaryMatch::match($password);
        // Match 0 is "pass" with rank 35.
        $this->assertEquals(log(35, 2), $matches[0]->getEntropy());
    }
}
