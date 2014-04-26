<?php
class Jetpack_HTML_Builder {
	private $tag;
	private $content;
	private $classes;
	private $attrs;
	private $css;
	private $raws;

	private static $no_closing_tag = array(
		'img',
		'input',
		'br',
		'hr',
		'frame',
		'area',
		'base',
		'basefont',
		'col',
		'isindex',
		'link',
		'meta',
		'param'
	);

	public function __construct() {
		$this->tag = '';
		$this->content = '';
		$this->classes = array();
		$this->attrs = array();
		$this->css = array();
		$this->raws = array();
	}

	private function build_attr_string() {
		$strs = array();
		foreach ( $this->attrs as $attr => $val ) {
			$strs[] = "$attr=\"$val\"";
		}
		return implode( ' ', $strs );
	}

	private function build_css_string() {
		$strs = array();
		foreach ( $this->css as $attr => $val ) {
			$strs[] = "$attr: $val;";
		}
		return implode( ' ', $strs );
	}

	public function build() {
		$attr_string = $this->build_attr_string();
		$css_string = $this->build_css_string();
		$css_string = empty( $css_string ) ? '' : "style=\"$css_string\"";
		$class_string = implode( ' ', $this->classes );
		$class_string = empty( $class_string ) ? '' : "class=\"$class_string\"";
		$raw_str = implode( ' ', $this->raws );

		if ( in_array( strtolower( $this->tag ), self::$no_closing_tag ) ) {
			return "<$this->tag $raw_str $attr_string $class_string $css_string />";
		}

		return "<$this->tag $raw_str $attr_string $class_string $css_string>$this->content</$this->tag>";
	}


	public function addClass() {
		$classes = func_get_args();
		foreach ( $classes as $class ) {
			$this->classes[] = $class;
		}
		return $this;
	}

	public function content( $content ) {
		if ( $content instanceof Jetpack_HTML_Builder ) {
			$content = $content->build();
		}

		$this->content .= $content;
		return $this;
	}

	// Alias for content
	public function addContent( $content ) {
		$this->content( $content );
	}

	public function tag( $tagName ) {
		$this->tag = $tagName;
		return $this;
	}

	public function css( $attr, $val ) {
		$this->css[$attr] = $val;
		return $this;
	}

	public function attr( $attr, $val ) {
		$this->attrs[$attr] = $val;
		return $this;
	}

	public function data( $attr, $val ) {
		$this->attrs["data-$attr"] = $val;
		return $this;
	}

	// Puts a raw string in the element tag
	public function raw( $raw_string ) {
		$this->raws[] = $raw_string;
		return $this;
	}

	// For neat setting of attributes, like so: ->src( 'hello.png' )
	public function __call( $name, $arguments ) {
		$this->attr( $name, $arguments[0] );
		return $this;
	}

	// For easy chaining when intializing the builder, passing in the tag too
	public static function element( $tagName = null ) {
		$builder = new Jetpack_HTML_Builder();
		if ( isset( $tagName ) ) {
			$builder = $builder->tag( $tagName );
		}
		return $builder;
	}
}
?>
