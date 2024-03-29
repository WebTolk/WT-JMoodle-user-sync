<?php
/**
 * @package       WT JMoodle user sync
 * @version       1.0.1
 * @Author        Sergey Tolkachyov, https://web-tolk.ru
 * @сopyright (c) March 2024 Sergey Tolkachyov. All rights reserved.
 * @license       GNU/GPL3 http://www.gnu.org/licenses/gpl-3.0.html
 * @since         1.0.0
 */

namespace Joomla\Plugin\User\Wtjmoodleusersync\Fields;
defined('_JEXEC') or die;

use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Webtolk\JMoodle\JMoodle;

class CheckusermethodsField extends NoteField
{

	protected $type = 'CheckusermethodsField';

	/**
	 * Flag is core_user_create_users available
	 * @var bool $core_user_create_users
	 * @since 1.0.0
	 */
	protected $core_user_create_users = false;

	/**
	 * Flag is core_user_update_users available
	 * @var bool $core_user_update_users
	 * @since 1.0.0
	 */
	protected $core_user_update_users = false;

	/**
	 * Flag is core_user_delete_users available
	 * @var bool $core_user_delete_users
	 * @since 1.0.0
	 */
	protected $core_user_delete_users = false;

	/**
	 * Method to get the field input markup for a spacer.
	 * The spacer does not have accept input.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.7.0
	 */
	protected function getInput()
	{
		$required_moodle_rest_api_methods = [
			'core_user_create_users',
			'core_user_update_users',
			'core_user_delete_users',
		];

		$moodle = new JMoodle();


		if (!$moodle::canDoRequest())
		{
			return '<div class="alert alert-danger row">
						<div class="col-2 h1">400</div>
						<div class="col-10">Can\'t make a request to Moodle</div>
					</div>';
		}

		$result_jmoodle = $moodle->request('core_webservice_get_site_info');
		if (count($result_jmoodle) == 0)
		{
			return '<div class="alert alert-danger row">
						<div class="col-2 h1">400</div>
						<div class="col-10">There is no Moodle host response</div>
					</div>';
		}
		if (isset($result_jmoodle['error_code']) && !empty($result_jmoodle['error_code']))
		{
			return '<div class="alert alert-danger row">
						<div class="col-2 h1">' . $result_jmoodle['error_code'] . '</div>
						<div class="col-10">' . $result_jmoodle['error_message'] . '</div>
					</div>';
		}

		if (!array_key_exists('sitename', $result_jmoodle) || empty($result_jmoodle['sitename']))
		{
			return '<div class="alert alert-danger row">
						<div class="col-2 h1">400</div>
						<div class="col-10">Moodle return wrong response</div>
					</div>';
		}

		foreach ($result_jmoodle['functions'] as $function){
			$function_name = $function['name'];
			if(in_array($function_name, $required_moodle_rest_api_methods)){
				$this->$function_name = true;
			}
		}

		$html   = [];
		$html[] = '<ul class="list-group list-group-flush">';

		if($this->core_user_create_users){
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_CREATE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CAN'));
			$badge = '<span class="badge bg-success me-3"><i class="fa-solid fa-check"></i></span>';
		} else {
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_CREATE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_NOT_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CANNOT'));
			$badge = '<span class="badge bg-danger me-3"><i class="fa-solid fa-xmark"></i></span>';
		}
		$html[] = '<li class="list-group-item">'.$badge.$message.'</li>';

		if($this->core_user_update_users){
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_UPDATE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CAN'));
			$badge = '<span class="badge bg-success me-3"><i class="fa-solid fa-check"></i></span>';
		} else {
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_UPDATE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_NOT_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CANNOT'));
			$badge = '<span class="badge bg-danger me-3"><i class="fa-solid fa-xmark"></i></span>';
		}
		$html[] = '<li class="list-group-item">'.$badge.$message.'</li>';

		if($this->core_user_delete_users){
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_DELETE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CAN'));
			$badge = '<span class="badge bg-success me-3"><i class="fa-solid fa-check"></i></span>';
		} else {
			$message = Text::sprintf(
				Text::_('PLG_WTJMOODLEUSERSYNC_CORE_USER_DELETE_USERS'),
				Text::_('PLG_WTJMOODLEUSERSYNC_METHOD_NOT_AVAILABLE'),
				Text::_('PLG_WTJMOODLEUSERSYNC_CANNOT'));
			$badge = '<span class="badge bg-danger me-3"><i class="fa-solid fa-xmark"></i></span>';
		}
		$html[] = '<li class="list-group-item">'.$badge.$message.'</li>';
		$html[] = '</ul>';

		return implode('', $html);
	}

	/**
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{

		return '';

	}

	/**
	 * Method to get the field title.
	 *
	 * @return  string  The field title.
	 *
	 * @since   1.7.0
	 */
	protected function getTitle()
	{
		return $this->getLabel();
	}

}

?>