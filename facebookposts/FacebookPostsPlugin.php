<?php
namespace Craft;

class FacebookPostsPlugin extends BasePlugin
{
    function getName()
    {
         return Craft::t('Facebook Posts');
    }

    function getVersion()
    {
        return '1.0';
    }

    function getDeveloper()
    {
        return 'lexbi';
    }

    function getDeveloperUrl()
    {
        return 'https://github.com/lexbi';
    }

    protected function defineSettings()
    {
        return array(
            'facebookAppId' => array(AttributeType::String, 'label' => 'Facebook App Id', 'default' => ''),
            'facebookAppSecret' => array(AttributeType::String, 'label' => 'Facebook App Secret', 'default' => ''),
            'facebookUser' => array(AttributeType::String, 'label' => 'Facebook User', 'default' => ''),
            'sectionId' => array(AttributeType::String, 'default' => ''),
			'entryTypeId' => array(AttributeType::String, 'default' => ''),
        );
    }
    public function getSettingsHtml()
    {
       return craft()->templates->render('facebookposts/settings', array(
           'settings' => $this->getSettings()
       ));
    }

    function onAfterInstall()
	{
		// Create the Facebook Posts Field Group

        $fieldGroup = new FieldGroupModel();
        $fieldGroup->name = 'Facebook Posts';

        if(craft()->fields->saveGroup($fieldGroup)){
            $groupId = $fieldGroup->id;
        }

		$group = new FieldGroupModel();
		$group->name = 'Facebook Posts';
		if (craft()->fields->saveGroup($group))
		{
			Craft::log('Facebook Posts field group created successfully.');

			$groupId = $group->id;
		}

		$facebookPostFields = array(
			'fbPostId' => 'FB Post Id',
			'fbPostMessage' => 'FB Post Message',
			'fbPostLink' =>	'FB Post Link',
			'fbPostFullPicture'	=>	'FB Post Full Picture',
			'fbPostSource' =>  'FB Post Source'
		);

		$facebookPostsEntryLayoutIds = array();

        foreach($facebookPostFields as $handle => $name) {
            if($handle != "fbPostFullPicture"){
                $fieldType = "PlainText";
            } else{
                $fieldType = "Assets";
            }
			$field = new FieldModel();
			$field->groupId = $groupId;
			$field->name = $name;
			$field->handle = $handle;
			$field->translatable = true;
			$field->type = 'PlainText';

            if (craft()->fields->saveField($field))
			{
				$facebookPostsEntryLayoutIds[] = $field->id;
			}

		}

		if ($facebookPostsEntryLayout = craft()->fields->assembleLayout(array('Facebook Posts' => $facebookPostsEntryLayoutIds), array()))
		{
			Craft::log('FB Posts Field Layout created successfully.');
		}
		else
		{
			Craft::log('Could not create the FB Posts Field Layout', LogLevel::Error);
			return false;
		}
		$facebookPostsEntryLayout->type = ElementType::Entry;

        // Create the FB Posts Channel
		Craft::log('Creating the FB Posts Channel.');
		$facebookPostsChannelSection = new SectionModel();
		$facebookPostsChannelSection->name = 'Facebook Posts';
		$facebookPostsChannelSection->handle = 'facebookPosts';
		$facebookPostsChannelSection->type = SectionType::Channel;
		$facebookPostsChannelSection->hasUrls = false;
		$facebookPostsChannelSection->enableVersioning = false;
		$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
		$locales[$primaryLocaleId] = new SectionLocaleModel(array(
			'locale' => $primaryLocaleId,
		));
		$facebookPostsChannelSection->setLocales($locales);
		if (craft()->sections->saveSection($facebookPostsChannelSection))
		{
			Craft::log('Facebook Posts Channel created successfully.');
		}
		else
		{
			Craft::log('Could not create the Facebook Posts Channel.', LogLevel::Warning);
			return false;
		}
		// Get the array of entry types for our new section
		$facebookPostsEntryTypes = $facebookPostsChannelSection->getEntryTypes();
		// There will only be one so get that
		$facebookPostsEntryType = $facebookPostsEntryTypes[0];
		$facebookPostsEntryType->hasTitleField = true;
		$facebookPostsEntryType->titleLabel 	= 'Title';
		$facebookPostsEntryType->setFieldLayout($facebookPostsEntryLayout);
		if (craft()->sections->saveEntryType($facebookPostsEntryType))
		{
			Craft::log('Facebook Posts Channel Entry Type saved successfully.');
		}
		else
		{
			Craft::log('Could not create the Facebook Posts Channel Entry Type.', LogLevel::Warning);
			return false;
		}
		// Save the settings based on the section and entry type we just created
		craft()->plugins->savePluginSettings($this,
			array(
				'sectionId' => $facebookPostsChannelSection->id,
				'entryTypeId' => $facebookPostsEntryType->id
			)
		);
	}

    function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
    }

}
