<?php
	function opml_import_domxml($link, $owner_uid) {

		if (is_file($_FILES['opml_file']['tmp_name'])) {
			$dom = domxml_open_file($_FILES['opml_file']['tmp_name']);

			if ($dom) {
				$root = $dom->document_element();

				$body = $root->get_elements_by_tagname('body');

				if ($body[0]) {			
					$body = $body[0];

					$outlines = $body->get_elements_by_tagname('outline');

					print "<table>";

					foreach ($outlines as $outline) {

						$feed_title = db_escape_string($outline->get_attribute('text'));

						if (!$feed_title) {
							$feed_title = db_escape_string($outline->get_attribute('title'));
						}

						$cat_title = db_escape_string($outline->get_attribute('title'));

						if (!$cat_title) {
							$cat_title = db_escape_string($outline->get_attribute('text'));
						}
	
						$feed_url = db_escape_string($outline->get_attribute('xmlUrl'));
						$site_url = db_escape_string($outline->get_attribute('htmlUrl'));

						if ($cat_title && !$feed_url) {

							db_query($link, "BEGIN");
							
							$result = db_query($link, "SELECT id FROM
								ttrss_feed_categories WHERE title = '$cat_title' AND
								owner_uid = '$owner_uid' LIMIT 1");

							if (db_num_rows($result) == 0) {

								printf(_("Adding category <b>%s</b>."), $cat_title);
								print "<br>";

								db_query($link, "INSERT INTO ttrss_feed_categories
									(title,owner_uid) 
								VALUES ('$cat_title', '$owner_uid')");
							}

							db_query($link, "COMMIT");
						}

//						print "$active_category : $feed_title : $feed_url<br>";

						if (!$feed_title || !$feed_url) continue;

						db_query($link, "BEGIN");

						$cat_id = null;

						$parent_node = $outline->parent_node();

						if ($parent_node && $parent_node->node_name() == "outline") {
							$element_category = $parent_node->get_attribute('title');
						} else {
							$element_category = '';
						}

						if ($element_category) {

							$result = db_query($link, "SELECT id FROM
									ttrss_feed_categories WHERE title = '$element_category' AND
									owner_uid = '$owner_uid' LIMIT 1");								

							if (db_num_rows($result) == 1) {	
								$cat_id = db_fetch_result($result, 0, "id");
							}
						}								

						$result = db_query($link, "SELECT id FROM ttrss_feeds WHERE
							(title = '$feed_title' OR feed_url = '$feed_url') 
							AND owner_uid = '$owner_uid'");

						print "<tr><td><a target='_new' href='$site_url'><b>$feed_title</b></a></b> 
							(<a target='_new' href=\"$feed_url\">rss</a>)</td>";

						if (db_num_rows($result) > 0) {
							print "<td>"._("Already imported.")."</td>";
						} else {

							if ($cat_id) {
								$add_query = "INSERT INTO ttrss_feeds 
									(title, feed_url, owner_uid, cat_id, site_url) VALUES
									('$feed_title', '$feed_url', '$owner_uid', 
										'$cat_id', '$site_url')";

							} else {
								$add_query = "INSERT INTO ttrss_feeds 
									(title, feed_url, owner_uid, site_url) VALUES
									('$feed_title', '$feed_url', '$owner_uid', '$site_url')";

							}

							db_query($link, $add_query);
							
							print "<td><b>"._('Done.')."</b></td>";
						}

						print "</tr>";
						
						db_query($link, "COMMIT");
					}

					print "</table>";

				} else {
					print "<div class=\"error\">"._("Error: can't find body element.")."</div>";
				}
			} else {
				print "<div class=\"error\">"._("Error while parsing document.")."</div>";
			}

		} else {
			print "<div class=\"error\">"._("Error: please upload OPML file.")."</div>";
		}

	}
?>