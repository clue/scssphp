<?php

require_once __DIR__ . "/../scss.inc.php";

class CompressTest extends PHPUnit_Framework_TestCase {
	public function setUp() {
		$this->scss = new scssc();
		$this->scss->setFormatter(new scss_formatter_compressed());
	}
	
	public function testCompressTwice(){
		$code = file_get_contents(__DIR__.'/inputs/variables.scss');
		
		$result = $this->scss->compile($code);
		
		$this->assertEquals($result,$this->scss->compile($result));
	}
	
	public function testCompressFormat(){
		// check removing trailing semicolons
		$this->assertEquals('@import "missing";a{border:0;content:";";border:0}b{border:0}',$this->scss->compile('@import "missing";a{border:0;content:";";border:0;}b{border:0;}'));
		
		// check removing excessive whitespace
		$this->assertEquals('body,a,a:hover{border:0}',$this->scss->compile('body, a,  a:hover  { border: 0 ; }'));
		
		// check removing empty blocks and comments
		$this->assertEquals('a{display:hidden}',$this->scss->compile('  /* comment */  b{a{ }} strong{;;;} a{display:hidden;;;} b{/*inner comment*/}'));
	}
	
	/**
	 * @dataProvider equalColorsProvider
	 */
	public function testCompressColor($in,$out) {
		$this->assertEquals('color:'.$out,$this->scss->compile('color:'.$in));
	}
	
	public function equalColorsProvider() {
		return $this->prepareSet(array(
			'red' => 'red', // unchanged color keyword as shortest
			'#000' => '#000', // unchanged short hex
			'#00ff00' => '#0f0', // short hex
			'#7abAaA' => '#7abaaa', // always use lowercase hex
			'rgb(255,255,255)' => '#fff', // short hex
			'rgb(123,121,121)' => '#7b7979', // hex instead of RGB notation
			'rgb( 0 , 0 , 255 )' => '#00f', // short hex for excessive whitespace
			'rgba(123,121,121,1)' => '#7b7979', // full alpha => use hex
			'rgba(123,121,121,2)' => '#7b7979', // cap excessive alpha to full alpha
			'rgba(123,121,121,0.9999)' => '#7b7979', // round alpha component
			'rgba(123,121,121,0.5)' => 'rgba(123,121,121,.5)', // remove useless leading zero
			'rgba(123,121,121,0)' => 'transparent', // zero alpha => shorthand transparent color keyword
			'opacify(transparent, 1)' => '#000', // fully opaque 'transparent' is actually black
			'opacify(transparent, 0.3)' => 'rgba(0,0,0,.3)', // work with transparent color keyword
			'opacify(WhiTe,1)' => '#fff' // color keywords are actually case insensitive
		));
	}
	
	/**
	 * @dataProvider equalNumbersProvider
	 */
	public function testCompressNumber($in,$out){
		$this->assertEquals('padding:'.$out,$this->scss->compile('padding:'.$in));
	}
	
	public function equalNumbersProvider(){
		return $this->prepareSet(array(
			'14px' => '14px', // unchanged
			'0px'  => '0', // remove zero unit
			'0em'  => '0',
			'0%'   => '0',
			'1.5em' => '1.5em',
			'0.5em' => '.5em', // remove leading zero
			'0.50pt' => '.5pt', // remove leading and trailing zero and keep unit
			'-4.0'	=> '-4', // remove useless fraction
			'4.000px' => '4px',
			'4.9999' => '5', // round to next number
			'4.99'   => '4.99',
			'-0.2pt' => '-.2pt', // remove leading zero for negative numbers
			'-0pt'	 => '0', // negative zero is still zero
			'+4%'	 => '4%', // remove useless postive number marker
			'+0em'	 => '0'
		));
	}
	
	private function prepareSet($set){
		return array_map(function($in, $out) { return array($in, $out); }, array_keys($set), $set);
	}

}
