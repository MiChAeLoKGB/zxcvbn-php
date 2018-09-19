<?php

namespace ZxcvbnPhp\Test;

use PHPUnit\Framework\TestCase;
use ZxcvbnPhp\Matcher;
use ZxcvbnPhp\Matchers\DictionaryMatch;

/**
 * @covers \ZxcvbnPhp\Matcher
 */
class MatcherTest extends TestCase
{
    public function testGetMatches()
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('jjj');
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
        $this->assertCount(1, $matches);

        $matches = $matcher->getMatches('&&&&&&&&&&&&&&&');
        $this->assertSame('repeat', $matches[0]->pattern, 'Pattern incorrect');
    }

    public function testEmptyString()
    {
        $matcher = new Matcher();
        $this->assertEmpty($matcher->getMatches(''), "doesn't match ''");
    }

    public function testMultiplePatterns()
    {
        $matcher = new Matcher();
        $password = 'r0sebudmaelstrom11/20/91aaaa';

        $expectedMatches = [
            ['dictionary', [ 0,  3]],
            ['dictionary', [ 0,  6]],
            ['dictionary', [ 2,  3]],
            ['dictionary', [ 3,  4]],
            ['dictionary', [ 3,  6]],
            ['dictionary', [ 4,  6]],
            ['dictionary', [ 7,  8]],
            ['dictionary', [ 7,  9]],
            ['dictionary', [ 7, 10]],
            ['dictionary', [ 8,  8]],
            ['dictionary', [ 8, 10]],
            ['sequence',   [11, 12]],
            ['dictionary', [11, 15]],
            ['dictionary', [13, 14]],
            ['dictionary', [15, 16]],
            ['repeat',     [16, 17]],
            ['date',       [16, 23]],
            ['sequence',   [20, 21]],
            ['dictionary', [24, 24]],
            ['dictionary', [24, 27]],
            ['repeat',     [24, 27]],
            ['dictionary', [25, 25]],
            ['dictionary', [26, 26]],
            ['dictionary', [27, 27]],
        ];

        $matches = $matcher->getMatches($password);
        foreach ($expectedMatches as $expectedMatch) {
            foreach ($matches as $id => $match) {
                if ($match->pattern === $expectedMatch[0] && $match->begin === $expectedMatch[1][0] && $match->end === $expectedMatch[1][1]) {
                    unset($matches[$id]);
                }
            }
        }
        $this->assertEmpty($matches, "matches multiple patterns");
    }

    /**
     * There's a similar test in DictionaryTest for this as well, but this specific test is for ensuring that the
     * user input gets passed from the Matcher class through to DictionaryMatch function.
     */
    public function testUserDefinedWords()
    {
        $matcher = new Matcher();
        $matches = $matcher->getMatches('_wQbgL491', ['PJnD', 'WQBG', 'ZhwZ']);

        $this->assertInstanceOf(DictionaryMatch::class, $matches[0], "user input match is correct class");
        $this->assertEquals('wQbg', $matches[0]->token, "user input match has correct token");
    }
}