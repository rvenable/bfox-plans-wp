<?php

	function bfox_read_menu()
	{
		global $wpdb, $bfox_history, $bfox_quicknote, $bfox_trans;

		// Override the global translation using the translation passed in
		// TODO3: Do we really need to override the global translation?
		if (!empty($_GET['trans_id'])) $bfox_trans = Translations::get_translation($_GET['trans_id']);

		// Try to get some search text
		$search_text = (string) $_GET['search'];

		// If reference text was passed in, try to create BibleRefs from it
		// If we can't create a valid BibleRefs, and there is no search text,
		//  use the reference text as search text
		if (!empty($_GET['bible_ref']))
		{
			$refs = RefManager::get_from_str($_GET['bible_ref']);
			if (!$refs->is_valid())
			{
				unset($refs);
				if (empty($search_text)) $search_text = $_GET['bible_ref'];
			}
		}

		// If we don't have a ref or search text yet, we should just create a bible reference
		if (!isset($refs) && empty($search_text))
		{
			// First try to create a BibleRefs from the last viewed references
			list($refs) = $bfox_history->get_refs_array();

			// If there is no history, use Genesis 1
			// TODO3: Test this
			if (!isset($refs) || !$refs->is_valid()) $refs = RefManager::get_from_str('Genesis 1');
		}

		if (isset($refs)) $bfox_quicknote->set_biblerefs($refs);

	?>

<div class="" id="bible_tool">
<h2 id='bible_text_main_ref'>Bible Viewer</h2>
	<div id="bible_note_popup"></div>
	<div id="verse_select_box">
		<a href="#close" id="verse_select_box_close" onclick="bfox_close_select_box()">X Close</a>
		<div id="verse_select_menu">
			<h1 id="verse_selected"><?php echo $refStr; ?></h1>
			<ul>
				<li><a href="">Commentaries</a></li>
				<li><a href="">View text without verse numbers</a></li>
				<li><a href="">View in Quick View</a></li>
				<li><a href="">Create a quick note</a></li>
			</ul>
		</div>
		<div id="edit_quick_note">
			<form action="" id="edit_quick_note_form">
				Enter note text:
				<input type="hidden" value="" id="edit_quick_note_id" />
				<textarea rows="1" style="width: 100%; height: auto;" class="edit_quick_note_input" id="edit_quick_note_text"></textarea>
				<input type="text" id="quick_note_bible_ref" name="quick_note_bible_ref" value="" disabled />
				<input type="button" value="<?php _e('Save'); ?>" class="button edit_quick_note_input" onclick="bfox_save_quick_note()" />
				<input type="button" value="<?php _e('New Note'); ?>" class="button edit_quick_note_input" onclick="bfox_new_quick_note()" />
				<input type="button" value="<?php _e('Delete'); ?>" class="button edit_quick_note_input" onclick="bfox_delete_quick_note()" />
				<div id="edit_quick_note_progress"></div>
			</form>
		</div>
	</div>
	<div id="bible_tool_header">
		<form id="bible_view_search" action="admin.php" method="get">
			<input type="hidden" name="page" value="<?php echo BFOX_READ_SUBPAGE; ?>" />
				<?php Translations::output_select($bfox_trans->id) ?>
			<input type="text" name="bible_ref" value="<?php echo $reflistStr; ?>" />
			<input type="submit" value="<?php _e('Search Bible', BFOX_DOMAIN); ?>" class="button" />
		</form>
		<ul id="bible_tool_options">
			<li><a id="verse_layout_toggle" class="button" onclick="bfox_toggle_paragraphs()">Switch to Verse View</a></li>
			<li><a class="button" onclick="bfox_toggle_quick_view()">Quick View</a></li>
		</ul>
	</div>
	<div id="bible_tool_body">
		<div id="bible_view">
			<?php
				if (!empty($search_text)):
					if (isset($refs)) $ref_where = $refs->sql_where();
					bfox_bible_text_search($search_text, $ref_where);
				elseif (isset($refs)): ?>
			<div id="bible_view_header">
				<h3><?php echo $refs->get_string() . " ($bfox_trans->short_name)" ?></h3>
				<?php echo bfox_get_ref_menu($refs, TRUE) ?>
			</div>
			<div id="bible_view_content">
				<?php echo Translations::get_verse_content($refs) ?>
			</div>
			<div id="bible_view_footer">
				<?php
					echo bfox_get_ref_menu($refs, FALSE);
					echo $refs->get_toc(TRUE);
				?>
			</div>
			<?php endif; ?>
		</div>
		<?php if (isset($refs)): ?>
		<div id="bible_quick_view">
			<div id="bible_quick_view_header">
				<ul class="bible_quick_view_menu">
					<li><a class="button" onclick="bfox_select_quick_view('bible_quick_view_scripture')">Scripture</a></li>
					<li><a class="button" onclick="bfox_select_quick_view('bible_quick_view_blogs')">Blogs</a></li>
					<li><a class="button" onclick="bfox_select_quick_view('bible_quick_view_dict')">Dictionary</a></li>
					<li><a class="button" onclick="bfox_select_quick_view('bible_quick_view_forum')">Forum</a></li>
					<li><a class="button" onclick="bfox_select_quick_view('bible_quick_view_audio')">Audio</a></li>
				</ul>
				<div id="bible_quick_view_scripture_header" class="bible_quick_view_menu_option">
					<h4 id="bible-text-progress">No Scripture</h4>
					<?php Translations::output_select($bfox_trans->id, TRUE) ?>
					<input type="text" name="new-bible-ref" id="new-bible-ref" size="16" value="" />
					<input type="button" class="button" id="view-bible-ref" value="Search" tabindex="3" />
					<input type="hidden" name="bible-request-url" id="bible-request-url" value="<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php" />
					<div id="bible_quick_view_scripture_menu"></div>
				</div>
				<div id="bible_quick_view_blogs_header" class="bible_quick_view_menu_option">
					This will display blog entries for this scripture.
				</div>
				<div id="bible_quick_view_dict_header" class="bible_quick_view_menu_option">
					This will display dictionary entries for this scripture.
				</div>
				<div id="bible_quick_view_forum_header" class="bible_quick_view_menu_option">
					This will display forum discussions for this scripture.
				</div>
				<div id="bible_quick_view_audio_header" class="bible_quick_view_menu_option">
					This will display audio bibles for this scripture.
				</div>
			</div>
			<div id="bible_quick_view_body">
				<div id="bible_quick_view_scripture_body" class="bible_quick_view_menu_option">
					<div id="bible-text">
						<p>This is the bible quick view. Try viewing <?php echo BfoxLinks::get_ref_link($refs, NULL, 'quick') ?></p>
					</div>
				</div>
				<div id="bible_quick_view_blogs_body" class="bible_quick_view_menu_option">
					<?php Commentaries::output_posts($refs); ?>
				</div>
				<div id="bible_quick_view_dict_body" class="bible_quick_view_menu_option">
					This will display dictionary entries for this scripture.
				</div>
				<div id="bible_quick_view_forum_body" class="bible_quick_view_menu_option">
					This will display forum discussions for this scripture.
				</div>
				<div id="bible_quick_view_audio_body" class="bible_quick_view_menu_option">
					This will display audio bibles for this scripture.
				</div>
			</div>
		</div>
		<? endif; ?>
	</div>
	<div id="bible_tool_footer">
	</div>
</div>

<?php
/*		echo '<table id="quick_note_list">';
		echo $bfox_quicknote->list_quicknotes();
		echo '</table>';*/

		// Update the read history to show that we viewed these scriptures
		if (isset($refs)) $bfox_history->update($refs);

/*	TODO2: Make sure everything on this list is in a task, then remove this list
	echo '<h2>Blog Post Commentaries</h2>';
	echo '<p><a href="">Write A Post</a></p>';
	echo '<h3>My Bible Study Blogs</h3><p>View posts from any Biblefox Bible Studies that you have joined or subscribed to.<br/>Check out the list of Commentary Blogs to find some you can subscribe to.<br/><a href="">Add Commentaries</a></p>';
	echo '<h3>My Friend Commentaries</h3><p>You can see what other users have written about this passage.<br/><a href="">Add Friends</a></p>';
	echo '<h2>Tools</h2>';
	echo '<h3>Bible Search</h3><ul><li>Reference</li><li>Text</li><li>Topic</li></ul>';
	echo '<h3>Random Passage</h3>';
	echo '<h3>My Reading Plans</h3>';
	echo '<h3>Create A Reading Plan</h3>';
	echo '<h3>Side by Side Passages</h3>';
	echo '<h3>Table of Contents</h3>';
	echo '<h3>Quick Table of Contents</h3>';
	echo '<h3>Bible Dictionary</h3>';
	echo '<h3>Bible Forum</h3>';
	echo '<h3>Bible Wiki</h3>';
	echo '<h3>Bible By Email</h3>';
	echo '<h3>Topical Cross References</h3><p>From the Topical Search</p>';
	echo '<h3>Hebrew</h3>';
	echo '<h3>Greek</h3>';*/

	}

?>