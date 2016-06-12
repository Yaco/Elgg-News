<?php
/**
 * Elgg News plugin
 * @package amapnews
 */

elgg_load_library('elgg:amapnews');

$group_guid = (int) get_input('group_guid');
$group_entity = get_entity($group_guid);

$user = elgg_get_logged_in_user_entity();
$staff = $user->news_staff;

// post news only for admins or groups owners and staff (if allowed by admins)
if (elgg_is_admin_logged_in() || (allow_post_on_groups() && elgg_instanceof($group_entity, 'group') && $group_entity->canEdit()) || $staff)	{
    
    // Get variables
    $title = get_input("title");
    $description = get_input("description");
    $excerpt = get_input("excerpt");
    $tags = get_input("tags");
    $access_id = (int) get_input("access_id");
    $guid = (int) get_input('amapnews_guid');
    $connected_guid = (int) get_input('connected_guid');
    $container_guid = get_input('container_guid', elgg_get_logged_in_user_guid());
    $comments_on = get_input("comments_on");

    elgg_make_sticky_form('amapnews');

    if (!$title) {
        register_error(elgg_echo('amapnews:save:missing_title'));
        forward(REFERER);
    }
    
    if (!$excerpt) {
        register_error(elgg_echo('amapnews:save:missing_excerpt'));
        forward(REFERER);
    }  
    
	// if not admin but group owners, check if a access level is limited only to group
	if (!elgg_is_admin_logged_in() && elgg_instanceof($group_entity, 'group') && $group_entity->canEdit())	{
		if ($access_id > 0 && $access_id < 3)	{
			register_error(elgg_echo('amapnews:save:notvalid_access_id'));
			forward(REFERER);
		}
	}
    
    // check whether this is a new object or an edit
    $new_entity = true;
    if ($guid > 0) {
		$new_entity = false;
    }
    
    if ($guid == 0) {
        $entity_unit = new ElggObject;
        $entity_unit->subtype = "amapnews";
        
        $entity_unit->container_guid = $container_guid;
        $new = true;
        // if no title on new upload, grab filename
        if (empty($title)) {
			$title = elgg_echo('amapnews:save:announcement');
        }        
    } else {
        $entity_unit = get_entity($guid);
        if (!$entity_unit->canEdit()) {
            system_message(elgg_echo('amapnews:save:failed'));
            forward(REFERRER);
        }
        if (!$title) {
                // user blanked title, but we need one
                $title = $entity_unit->title;
        }    
    }

    $tagarray = string_to_tag_array($tags);

    $entity_unit->title = $title;
    $entity_unit->description = $description;
    $entity_unit->excerpt = $excerpt;
    $entity_unit->tags = $tagarray;
    $entity_unit->connected_guid = $connected_guid;
    $entity_unit->container_guid = $container_guid;
    $entity_unit->comments_on = $comments_on;
    $entity_unit->access_id = $access_id;

    if ($entity_unit->save()) {
        elgg_clear_sticky_form('amapnews');
        
        system_message(elgg_echo('amapnews:save:success'));

        //add to river only if new
        elgg_create_river_item(array(
			'view' => 'river/object/amapnews/create',
			'action_type' => 'create',
			'subject_guid' => $entity_unit->owner_guid,
            'target_guid' => $entity_unit->container_guid,
			'object_guid' => $entity_unit->getGUID(),
		));

		if (elgg_instanceof($group_entity, 'group')) {
			forward(elgg_get_site_url() . "news/group/".$group_entity->guid."/all");
		}
		else {
			forward(elgg_get_site_url() . "news");
		}
		
    } else {
        register_error(elgg_echo('amapnews:save:failed'));
        forward("amapnews");
    }

} 
else    {  
    register_error(elgg_echo('amapnews:add:noaccessforpost'));  
    forward(REFERER);    
}
