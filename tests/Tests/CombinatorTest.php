<?php

class CombinatorTest extends PHPUnit_Framework_TestCase
{
    public function testSeq() {
        $_ = Colander\seq([
            function ($in) {
                return $in .= 'a';
            },
            function ($in) {
                return $in .= 'b';
            }
        ]);

        $this->assertEquals('xab', $_('x'));
    }

    public function testSeqFail() {
        try {
            $_ = Colander\seq([
                function ($in) {
                    return $in .= 'a';
                },
                function ($in) {
                    return $in .= 'b';
                }
            ]);

            $this->assertEquals('xab', $_('x'));
        } catch (Colander\ValidationException $e) {
            
        }
    }

    public function testSeq_() {
    }

    public function testSeq_Fail() {
    }

    public function testMap() {
    }

    public function testMapFail() {
    }

    public function testMapS() {
    }

    public function testMapSFail() {
    }

    public function testMap_() {
    }

    public function testMap_Fail() {
    }

    public function testMap_S() {
    }

    public function testMap_SFail() {
    }

    public function testLst() {
    }

    public function testLst_() {
    }

    public function testTuple() {
    }

    public function testTuple_() {
    }
}
