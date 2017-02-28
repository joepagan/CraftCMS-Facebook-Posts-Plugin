<?php

namespace Craft;

class FacebookPosts_ApiService extends BaseApplicationComponent
{

    private function getMySetting($value)
    {
        $plugin = craft()->plugins->getPlugin('FacebookPosts');
        $settings = $plugin->getSettings();
        return $settings->$value;
    }

    public function init(){

        $facebookAppId = $this->getMySetting("facebookAppId");
        $facebookAppSecret = $this->getMySetting("facebookAppSecret");
        $facebookUser = $this->getMySetting("facebookUser");
        $sectionId = $this->getMySetting("sectionId");
        $entryTypeId = $this->getMySetting("entryTypeId");

        $fb = new \Facebook\Facebook([
            'app_id' => $facebookAppId,
            'app_secret' => $facebookAppSecret,
            'default_graph_version' => 'v2.8',
            'default_access_token' => $facebookAppId ."|". $facebookAppSecret, // optional
        ]);

        $posts = $fb->get('/'.$facebookUser.'/posts?limit=5&fields=full_picture,message,link,source,id,created_time')->getDecodedBody()['data'];

        // loop of existing facebook ids
        $existingPosts = craft()->elements->getCriteria(ElementType::Entry);
        $existingPosts->sectionId = $sectionId;
        $existingPosts->type = $entryTypeId;
        $existingPosts->limit = null;
        $existingPosts->find();

        $existingPostsIds = array();

        foreach($existingPosts as $ePost){
            $existingPostsIds[] = $ePost->getContent()->fbPostId;
        }

        foreach($posts as $post){
            if(!in_array($post['id'], $existingPostsIds)){
                $this->createEntry($post, $sectionId, $entryTypeId);
            }
        }

    }

    public function createEntry($post, $sectionId, $entryTypeId){

        $entry = new EntryModel();
        $entry->sectionId = $sectionId;
        $entry->authorId = 1;
        $entry->enabled = true;

        $postMessage = isset($post['message']) ? $post['message'] : "";
        $postId = isset($post['id']) ? $post['id'] : "";
        $postLink = isset($post['link']) ? $post['link'] : "";
        $postFullPicture = isset($post['full_picture']) ? $post['full_picture'] : "";
        $postSource = isset($post['source']) ? $post['source'] : "";
        $postCreatedAt = isset($post['created_time']) ? $post['created_time'] : "";

        if(isset($postMessage)){
            $entry->getContent()->title = $postMessage;
        }else{
            $entry->getContent()->title = $postId;
        }
        $entry->postDate = strtotime($postCreatedAt);

        $entry->setContentFromPost(array(
            "title" => $postMessage,
            "fbPostMessage" => $postMessage,
            "fbPostId" => $postId,
            "fbPostLink" => $postLink,
            "fbPostFullPicture" => $postFullPicture,
            "fbPostSource" => $postSource
        ));

        $this->saveEntry($entry);

    }

    private function saveEntry($entry)
	{
		$success = craft()->entries->saveEntry($entry);

		// If the attempt failed
		if (!$success)
		{
			Craft::log('Couldn’t save entry ' . $entry->getContent()->id, LogLevel::Warning);
		}
	}


} // end FacebookPosts_RetrivePostsService
