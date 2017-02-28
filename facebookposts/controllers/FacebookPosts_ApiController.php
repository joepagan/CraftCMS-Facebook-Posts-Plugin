<?php

namespace Craft;

// http://jbh.dev:81/actions/FacebookPosts/RetrivePosts/RetriveFacebookPosts

class FacebookPosts_ApiController extends BaseController
{

    /**
     * @var    bool|array Allows anonymous access to this controller's actions.
     * @access protected
     */
    protected $allowAnonymous = array('actionRetriveFacebookPosts');

    /**
     * Handle a request going to our plugin's index action URL, e.g.: actions/ebaySync
     */
    public function actionRetriveFacebookPosts()
    {
        $retrivePosts = new FacebookPosts_ApiService();
        $retrivePosts->init();
    }
}
