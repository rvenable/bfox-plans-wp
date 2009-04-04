<?php

class BlogPost
{
	public $id, $type, $title, $content, $ref_str, $excerpt;
	public function __construct($title, $content, $ref_str = '')
	{
		$this->id = 0;
		$this->type = 'post';
		$this->title = $title;
		if (is_array($content)) $content = implode("\n", $content);
		$this->content = $content;
		$this->ref_str = $ref_str;
		$this->excerpt = '';
	}

	public function get_string()
	{
		return "Blog $this->type:\nTitle: $this->title\nBible References: $this->ref_str\nExcerpt:$this->excerpt\nContent:\n$this->content\n";
	}

	public function output()
	{
		return "<div>Title: $this->title<br/>Bible References: $this->ref_str</div><div>Excerpt:<br/>$this->excerpt</div><div>Content:<br/>$this->content</div>";
	}

	public function get_array($globals = array())
	{
		return array_merge(array(
				'ID' => $this->id,
				'post_type' => $this->type,
				'post_content' => $this->content,
				'post_title' => $this->title,
				'post_excerpt' => $this->excerpt
			),
			$globals
		);
	}
}

class BlogPage extends BlogPost
{
	public function __construct($title, $content, $ref_str = '')
	{
		parent::__construct($title, $content, $ref_str);
		$this->type = 'page';
	}
}

abstract class TxtToBlog
{
	const dir = BFOX_TEXTS_DIR;
	const divider = '__________________________________________________________________';
	const prev_posts_option = 'bfox_txt_to_blog_prev_posts';
	protected $file;
	protected $posts;
	private $post_index;
	private $warnings;
	private $global_post_vals;

	public function get_post_indexing_title(BlogPost $post)
	{
		return "{$post->type}_{$this->global_post_vals['post_category']}:$post->title";
	}

	public function update()
	{
		return $this->update_posts($this->parse_file());
	}

	public function update_posts($posts)
	{
		$prev_posts = get_option(self::prev_posts_option, array());

		foreach ($posts as &$post)
		{
			$index = $this->get_post_indexing_title($post);
			if (isset($prev_posts[$index]))
			{
				$post->id = $prev_posts[$index];
				unset($prev_posts[$index]);
			}
		}

		// Remove any remaining posts
		foreach ($prev_posts as $id)
		{
			echo "Deleting: $id<br/>";
			wp_delete_post($id);
		}

		$prev_posts = array();
		foreach ($posts as $post) $prev_posts[$this->get_post_indexing_title($post)] = wp_insert_post($post->get_array($this->global_post_vals));

		update_option(self::prev_posts_option, $prev_posts);
		pre($prev_posts);

		return count($prev_posts);
	}

	public function parse_file($file = '')
	{
		if (empty($file)) $file = $this->file;
		$file = self::dir . "/$file";
		$lines = file($file);

		$sections = array();
		foreach ($lines as $line)
		{
			$line = trim($line);
			if (self::divider == $line) $section_num++;
			else $sections[$section_num] []= $line;
		}

		$this->posts = array();
		$this->post_index = 0;
		foreach ($sections as $section)
			$this->parse_section($section);

		return $this->posts;
	}

	protected function add_post(BlogPost $post, $index = 0)
	{
		if (empty($index)) $index = $this->post_index++;

		$this->posts[$index] = $post;

		return $index;
	}

	protected function set_global_category($category)
	{
		$this->global_post_vals['post_category'] = $category;
	}

	protected function set_global_publish_status($status = 'publish')
	{
		$this->global_post_vals['post_status'] = $status;
	}

	protected abstract function parse_section($section);

	protected static function parse_title_body($body)
	{
		while (!is_null($title = array_shift($body)) && empty($title));
		return array($title, $body);
	}

	protected function warning($str)
	{
		$this->warnings []= $str;
	}

	public function print_warnings()
	{
		return implode("<br/>", $this->warnings);
	}

	public static function footnote_code($note)
	{
		return "[footnote]{$note}[/footnote]";
	}

	public static function link_code($page, $title = '')
	{
		if (empty($title)) $title = $page;
		return "[link page='$page']{$title}[/link]";
	}

	public static function bible_code($ref, $title = '')
	{
		if (empty($title)) $title = $ref;
		return "[bible ref='$ref']{$title}[/bible]";
	}
}

class MhccTxtToBlog extends TxtToBlog
{
	const file = 'mhcc.txt';
	private $book_index, $book_names, $book_tocs;

	function __construct()
	{
		$this->file = self::file;
		$this->set_global_category(0);
		$this->set_global_publish_status();
	}

	public function update()
	{
		$posts = $this->parse_file();
		return $this->update_posts(array($posts[3]));
	}

	public function parse_file($file = '')
	{
		$this->book_toc = array();
		$posts = parent::parse_file($file);

		// Make some hardcoded changes
		unset($posts[1]);
		$posts[2]->title = 'Matthew Henry\'s Commentary on the Bible';
		$posts[2]->content = 'An abridgment of the 6 volume "Matthew Henry\'s Commentary on the Bible".';

		// Use the book content as an except before we add the TOC
		foreach ($this->book_names as $book => $name) $posts[$book]->excerpt = $posts[$book]->content;

		foreach ($this->book_tocs as $index => $toc)
		{
			$posts[$index]->content .= "<h4>Chapters</h4><ul>$toc</ul>";
			$posts[2]->content .= "<h4>" . self::link_code($this->book_names[$index]) . "</h4><ul>$toc</ul>";
		}

		return $posts;
	}

	protected function parse_section($section)
	{
		list ($title, $body) = self::parse_title_body($section);
		if (preg_match('/chapter\s*(\d+)/i', $title, $match))
		{
			$chapter = $match[1];
			$ref = $this->book_names[$this->book_index] . " $chapter";
			$this->book_tocs[$this->book_index] .= "<li style='display: inline; padding-right: 10px;'>" . self::link_code($ref, $chapter) . "</li>";

			self::parse_chapter($ref, $body);
		}
		else if (BibleMeta::get_book_id($title))
		{
			$this->book_index = $this->add_post(new BlogPost($title, $body, $title));
			$this->book_names[$this->book_index] = $title;
			$this->book_tocs[$this->book_index] = '';
		}
		else $this->add_post(new BlogPage($title, $body));
	}

	protected function parse_chapter($chapter, $body)
	{
		$verse_num_pattern = '\d[\s\d,-]*';

		// Parse the chapter body into an outline section and verse sections
		$sections = array();
		$key = '';
		foreach ($body as $line)
		{
			if (preg_match('/chapter\s*outline/i', $line)) $key = 'outline';
			elseif (preg_match('/verses?\s*(' . $verse_num_pattern . ')/i', $line, $match)) $key = $match[1];
			elseif (!empty($key)) $sections[$key] []= $line;
		}
		$outline = (array) $sections['outline'];
		unset($sections['outline']);

		// Parse the outline section to find the titles for the verse sections
		$verse_titles = array();
		$verse_title_key = '';
		$verse_title = '';
		foreach ($outline as $line)
		{
			if (preg_match('/\((' . $verse_num_pattern . ')\)/i', $line, $match))
			{
				$verse_title_key = $match[1];
				$verse_title = trim(trim($verse_title), '.');
				$verse_titles[$verse_title_key] = $verse_title;
				$verse_title = '';
			}
			else $verse_title .= " $line";
		}

		// Create a new outline page with links to verse blog posts and bible references
		$outline = '';
		foreach ($verse_titles as $verse => $verse_title)
			$outline .= "<li><h4>" . self::bible_code("$chapter:$verse", "Verses ${verse}") . "</h4>" . self::link_code($verse_title) . "</li>";

		// Create the array of blog posts, starting with the outline post, followed by all the verse posts
		$this->add_post(new BlogPost($chapter, "<ol>$outline</ol>", $chapter));
		foreach ($sections as $key => $content)
			$this->add_post(new BlogPost($verse_titles[$key], $content, "$chapter:$key"));
	}
}

class CalcomTxtToBlog extends TxtToBlog
{
	const file = 'calcom/calcom01.txt';
	private $footnotes;

	function __construct()
	{
		$this->file = self::file;
		$this->footnotes = array();
	}

	private function insert_footnote($match)
	{
		$footnote = $match[0];
		if (!isset($this->footnotes[$match[1]])) $this->warning("Missing footnote: $match[0]");
		else
		{
			$footnote = self::footnote_code($this->footnotes[$match[1]]);
			unset($this->footnotes[$match[1]]);
		}
		return $footnote;
	}

	public function parse_file($file = '')
	{
		$posts = parent::parse_file($file);

		foreach ($this->footnotes as &$footnote) $footnote = trim($footnote);

		// Replace all the footnotes
		foreach ($posts as &$post)
		{
			$count++;
			$post->content = preg_replace_callback('/\[(\d+)\]/', array($this, 'insert_footnote'), $post->content);
		}

		return $posts;
	}

	protected function parse_section($section)
	{
		list ($title, $body) = self::parse_title_body($section);
		$refs = RefManager::get_from_str($title);

		$posts = array();
		if ($refs->is_valid()) $posts = $this->parse_bible_refs($refs, $title, $body);
		elseif (preg_match('/^\[\d+\]/', $title)) $this->parse_footnotes($section);
		else $this->add_post(new BlogPost($title, $body));
	}

	protected function parse_bible_refs(BibleRefs $refs, $title, $body)
	{
		$verses = array();
		foreach ($body as $line)
		{
			if (preg_match('/^(\d+)\.?(.*)$/', $line, $match))
			{
				$verse_key = $match[1];
				$verse_count = count($verses[$verse_key]);
				$verses[$verse_key][$verse_count] = $match[2];
			}
			else $verses[$verse_key][$verse_count] .= " $line";
		}

		list(list($verse_start)) = $refs->get_sets();
		$verse_ref = new BibleVerse($verse_start);

		foreach ($verses as $verse_num => $verse)
		{
			$content = "<blockquote>{$verse[0]}</blockquote>";
			$content .= "<blockquote>{$verse[1]}</blockquote>";
			$content .= "<p>{$verse[2]}</p>";
			$verse_ref->set_ref($verse_ref->book, $verse_ref->chapter, $verse_num);
			$this->add_post(new BlogPost($verse_ref->get_string(), $content, $verse_ref->get_string()));
		}
	}

	protected function parse_footnotes($section)
	{
		$key = '';
		foreach ($section as $line)
		{
			if (preg_match('/^\[(\d+)\](.*)$/', $line, $match))
			{
				$key = $match[1];
				$this->footnotes[$key] = $match[2];
			}
			else $this->footnotes[$key] .= " $line";
		}
	}

}

?>