<?php

$LANG->includeLLFile('EXT:sh_scoutnet_kalender/editor/locallang.xml');


$BE_USER->modAccess($MCONF, 1);

require_once (t3lib_extMgm::extPath('sh_scoutnet_webservice') . 'sn/class.tx_shscoutnetwebservice_sn.php');

// ***************************
// Script Classes
// ***************************
class SC_mod_user_scoutnet_kalender_editor_index extends t3lib_SCbase {

	protected $pageinfo;

	/**
	 * Initializes the Module
	 *
	 * @return	void
	 */
	public function __construct() {
		parent::init();

			// initialize document
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(t3lib_extMgm::extPath('sh_scoutnet_kalender') . 'editor/template.html');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->getPageRenderer()->loadScriptaculous('effects,dragdrop');
		//$this->doc->addStyleSheet( 'tx_shscoutnetkalender', t3lib_extMgm::siteRelPath('sh_scoutnet_kalender') . 'editor/style.css');
	       
		
		if (version_compare(TYPO3_version, '4.3', '<')) {
			$this->doc->addStyleSheet('tx_shscoutnetkalender', t3lib_extMgm::siteRelPath('sh_scoutnet_kalender') . 'editor/style.css');
			$this->doc->addStyleSheet('tx_shscoutnetkalender', t3lib_extMgm::siteRelPath('sh_scoutnet_kalender') . 'editor/kalender.css');
		} else {
			$this->doc->JScode .= '<link rel="stylesheet" type="text/css" href="' . t3lib_div::createVersionNumberedFilename(t3lib_div::resolveBackPath($this->doc->backPath . t3lib_extMgm::extRelPath('sh_scoutnet_kalender') . 'editor/style.css')) . '" />';
			$this->doc->JScode .= '<link rel="stylesheet" type="text/css" href="' . t3lib_div::createVersionNumberedFilename(t3lib_div::resolveBackPath($this->doc->backPath . t3lib_extMgm::extRelPath('sh_scoutnet_kalender') . 'editor/kalender.css')) . '" />';
		}

	}

	/**
	 * Creates the module's content. In this case it rather acts as a kind of #
	 * dispatcher redirecting requests to specific tasks.
	 *
	 * @return	void
	 */
	public function main() {

		

		$docHeaderButtons = $this->getButtons();

		$markers = array();

		$this->doc->JScodeArray[] = '
			script_ended = 0;
			function jumpToUrl(URL) {
				document.location = URL;
			}
		';
		$this->doc->postCode='
			<script language="javascript" type="text/javascript">
				script_ended = 1;
				if (top.fsMod) {
					top.fsMod.recentIds["web"] = 0;
				}
			</script>
		';

			// compile document
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(
				0,
				'SET[mode]',
				$this->MOD_SETTINGS['mode'],
				$this->MOD_MENU['mode']
			);
		$markers['CONTENT'] = $this->content;

		$markers['EBENE_LONG_NAME'] = "Diozese Köln";

		$markers['HEADER1_LABEL'] = $GLOBALS['LANG']->getLL('header1Label');
		$markers['BEGIN_LABEL'] = $GLOBALS['LANG']->getLL('beginLabel');
		$markers['END_LABEL'] = $GLOBALS['LANG']->getLL('endLabel');
		$markers['TITLE_LABEL'] = $GLOBALS['LANG']->getLL('titleLabel');
		$markers['ACTION_LABEL'] = $GLOBALS['LANG']->getLL('actionLabel');



		$termin_template = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate,'###TERMIN_TEMPLATE###');
		$year_change_template = t3lib_parsehtml::getSubpart($this->doc->moduleTemplate,'###YEAR_CHANGE_TEMPLATE###');
		$last_modified_template = t3lib_parsehtml::getSubpart($termin_template,'###LAST_MODIFIED###');


		$events = array();
		try {
			$SN = new tx_shscoutnetwebservice_sn();
			$filter = array(
				'order' => 'start_time desc',
				);
			$ids = array(4);
			$events = $SN->get_events_for_global_id_with_filter($ids,$filter);

		$termine = '';
		foreach ($events as $event) {

			if($previous_year != strftime('%Y',$event['Start'])) {
				$previous_year = strftime('%Y',$event['Start']);
				$termine .= t3lib_parsehtml::substituteMarkerArray($year_change_template,array('YEAR'=>strftime('%Y',$event['Start'])),'###|###');

			}


			$start_date = substr(strftime("%A",$event['Start']),0,2).",&nbsp;".strftime("%d.%m.%Y",$event['Start']);
			$date = $start_date;
			$end_date = '';

			if (isset($event['End']) && strftime("%d%m%Y",$event['Start']) != strftime("%d%m%Y",$event['End']) ) {
				$date .= "&nbsp;-&nbsp;";
				$end_date = substr(strftime("%A",$event['End']),0,2).",&nbsp;".strftime("%d.%m.%Y",$event['End']);
				$date .= $end_date;
			}

			$time = '';
			$start_time = '';
			$end_time = '';
			if ($event['All_Day'] != 1) {
				$start_time = strftime("%H:%M",$event['Start']);
				$time = $start_time;


				if (isset($event['End']) && strftime("%H%M",$event['Start']) != strftime("%H%M",$event['End']) ) {
					$time .= "&nbsp;-&nbsp;";
					$end_time = strftime("%H:%M",$event['End']);
					$time .= $end_time;
				}
			}

			$date_with_time = $start_date.(($start_time != '')?',&nbsp;'.$start_time:'').(($end_date.$end_time != '')?' '.$GLOBALS['LANG']->getLL('to').' ':'').($end_date != ''?$end_date:'').(($end_date.$end_time != '')?',&nbsp;':'').($end_time != ''?$end_time:'');



			$termin_markers = array(

				'TITEL' => nl2br(htmlentities($event['Title'],ENT_COMPAT,'UTF-8')),
				'DATE_WITH_TIME' => $date_with_time,

				'CREATED_LABEL' => $GLOBALS['LANG']->getLL('createdLabel'),
				'LAST_MODIFIED_LABEL' => $GLOBALS['LANG']->getLL('lastModifiedLabel'),

				'CREATED_BY' => $event['Created_By'],
				'CREATED_AT' => strftime("%d.%m.%Y %H:%M",$event['Created_At']),

				'LAST_MODIFIED_BY' => $event['Last_Modified_By'],
				'LAST_MODIFIED_AT' => strftime("%d.%m.%Y %H:%M",$event['Last_Modified_At']),

				'EDIT_LINK' => '<a href="'.$this->MCONF['_'].'&foo=baa">» '.$GLOBALS['LANG']->getLL('edit').'</a>',
				'USE_AS_TEMPLATE_LINK' => '<a href="">» '.$GLOBALS['LANG']->getLL('useAsTemplate').'</a>',
				'DELETE_LINK' => '<a href="">» '.$GLOBALS['LANG']->getLL('delete').'</a>',
			);


			$last_modified = isset($event['Last_Modified_By']) && $event['Last_Modified_By'] != ''?$last_modified_template:'';
			
			$termine .= t3lib_parsehtml::substituteMarkerArray(t3lib_parsehtml::substituteSubpart($termin_template,'###LAST_MODIFIED###',$last_modified),$termin_markers,'###|###');
		}


		$markers['TERMINE'] = $termine;


		} catch(Exception $e) {
			$this->content = '<span class="termin">'.$GLOBALS['LANG']->getLL('snkDown').'</span>';
		}

		// Build the <body> for the module
		$this->content = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$this->content .= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);


		return;
	}

	/**
	 * Prints out the module's HTML
	 *
	 * @return	void
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Generates the module content by calling the selected task
	 *
	 * @return	void
	 */
	protected function renderModuleContent() {
		$title = $content = $actionContent = '';
		$chosenTask	= (string)$this->MOD_SETTINGS['function'];

			// render the taskcenter task as default
		if (empty($chosenTask) || $chosenTask == 'index') {
			$chosenTask = 'taskcenter.tasks';
		}

			// remder the task
		list($extKey, $taskClass) = explode('.', $chosenTask, 2);
		$title = $GLOBALS['LANG']->sL($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extKey][$taskClass]['title']);

		if (class_exists($taskClass)) {
			$taskInstance = t3lib_div::makeInstance($taskClass, $this);

			if ($taskInstance instanceof tx_taskcenter_Task) {
					// check if the task is restricted to admins only
				if ($this->checkAccess($extKey, $taskClass)) {
					$actionContent .= $taskInstance->getTask();
				} else {
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$GLOBALS['LANG']->getLL('error-access', TRUE),
						$GLOBALS['LANG']->getLL('error_header'),
						t3lib_FlashMessage::ERROR
					);
					$actionContent .= $flashMessage->render();
				}
			} else {
					// error if the task is not an instance of tx_taskcenter_Task
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					sprintf($GLOBALS['LANG']->getLL('error_no-instance', TRUE), $taskClass, 'tx_taskcenter_Task'),
					$GLOBALS['LANG']->getLL('error_header'),
					t3lib_FlashMessage::ERROR
				);
				$actionContent .= $flashMessage->render();
			}
		} else {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->sL('LLL:EXT:taskcenter/task/locallang.xml:mlang_labels_tabdescr'),
				$GLOBALS['LANG']->sL('LLL:EXT:taskcenter/task/locallang.xml:mlang_tabs_tab'),
				t3lib_FlashMessage::INFO
			);
			$actionContent .= $flashMessage->render();
		}

		$content = '<div id="taskcenter-main">
						<div id="taskcenter-menu">' . $this->indexAction() . '</div>
						<div id="taskcenter-item" class="' . htmlspecialchars($extKey . '-' . $taskClass) . '">' .
							$actionContent . '
						</div>
					</div>';

		$this->content .= $content;
	}

	/**
	 * Generates the information content
	 *
	 * @return	void
	 */
	protected function renderInformationContent() {
		$content = $this->description (
			$GLOBALS['LANG']->getLL('mlang_tabs_tab'),
			$GLOBALS['LANG']->sL('LLL:EXT:taskcenter/task/locallang.xml:mlang_labels_tabdescr')
		);

		$content .= $GLOBALS['LANG']->getLL('taskcenter-about');

		if ($GLOBALS['BE_USER']->isAdmin()) {
			$content .= '<br /><br />' . $this->description (
				$GLOBALS['LANG']->getLL('taskcenter-adminheader'),
				$GLOBALS['LANG']->getLL('taskcenter-admin')
			);
		}

		$this->content .= $content;
	}

	/**
	 * Render the headline of a task including a title and an optional description.
	 *
	 * @param	string		$title: Title
	 * @param	string		$description: Description
	 * @return	string formatted title and description
	 */
	public function description($title, $description='') {
		if (!empty($description)) {
			$description = '<p class="description">' .	nl2br(htmlspecialchars($description)) . '</p><br />';
		}
		$content = $this->doc->section($title, $description, FALSE, TRUE);

		return $content;
	}

	/**
	 * Render a list of items as a nicely formated definition list including a
	 * link, icon, title and description.
	 * The keys of a single item are:
	 * 	- title:				Title of the item
	 * 	- link:					Link to the task
	 * 	- icon: 				Path to the icon or Icon as HTML if it begins with <img
	 * 	- description:	Description of the task, using htmlspecialchars()
	 * 	- descriptionHtml:	Description allowing HTML tags which will override the
	 * 											description
	 *
	 * @param	array		$items: List of items to be displayed in the definition list.
	 * @param	boolean		$mainMenu: Set it to TRUE to render the main menu
	 * @return	string	definition list
	 */
	public function renderListMenu($items, $mainMenu = FALSE) {
		$content = $section = '';
		$count = 0;

			// change the sorting of items to the user's one
		if ($mainMenu) {
			$this->doc->getPageRenderer()->addJsFile(t3lib_extMgm::extRelPath('taskcenter') . 'res/tasklist.js');
			$userSorting = unserialize($GLOBALS['BE_USER']->uc['taskcenter']['sorting']);
			if (is_array($userSorting)) {
				$newSorting = array();
				foreach($userSorting as $item) {
					if(isset($items[$item])) {
						$newSorting[] = $items[$item];
						unset($items[$item]);
					}
				}
				$items = $newSorting + $items;
			}
		}

		if (is_array($items) && count($items) > 0) {
			foreach($items as $item) {
				$title = htmlspecialchars($item['title']);

				$icon = $additionalClass = $collapsedStyle = '';
					// Check for custom icon
				if (!empty($item['icon'])) {
					if (strpos($item['icon'], '<img ') === FALSE) {
						$absIconPath = t3lib_div::getFileAbsFilename($item['icon']);
							// If the file indeed exists, assemble relative path to it
						if (file_exists($absIconPath)) {
							$icon = $GLOBALS['BACK_PATH'] . '../' . str_replace(PATH_site, '', $absIconPath);
							$icon = '<img src="' . $icon . '" title="' . $title . '" alt="' . $title . '" />';
						}
						if (@is_file($icon)) {
							$icon = '<img' . t3lib_iconworks::skinImg($GLOBALS['BACK_PATH'], $icon, 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />';
						}
					} else {
						$icon = $item['icon'];
					}
				}


				$description = (!empty($item['descriptionHtml'])) ? $item['descriptionHtml'] : '<p>' . nl2br(htmlspecialchars($item['description'])) . '</p>';

				$id = $this->getUniqueKey($item['uid']);

					// collapsed & expanded menu items
				if ($mainMenu && isset($GLOBALS['BE_USER']->uc['taskcenter']['states'][$id]) && $GLOBALS['BE_USER']->uc['taskcenter']['states'][$id]) {
					$collapsedStyle = 'style="display:none"';
					$additionalClass = 'collapsed';
				} else {
					$additionalClass = 'expanded';
				}

					// first & last menu item
				if ($count == 0) {
					$additionalClass .= ' first-item';
				} elseif ($count + 1 === count($items)) {
					$additionalClass .= ' last-item';
				}

					// active menu item
				$active = ((string) $this->MOD_SETTINGS['function'] == $item['uid']) ? ' active-task' : '';

					// Main menu: Render additional syntax to sort tasks
				if ($mainMenu) {
					$dragIcon = '<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/move.gif', 'width="16" height="16" hspace="2"') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.move', 1) . '" alt="" />';
					$section = '<div class="down">&nbsp;</div>
								<div class="drag">' . $dragIcon . '</div>';
					$backgroundClass = 't3-row-header ';
				}

				$content .= '<li class="' . $additionalClass . $active . '" id="el_' .$id . '">
								' . $section . '
								<div class="image">' . $icon . '</div>
								<div class="' . $backgroundClass . 'link"><a href="' . $item['link'] . '">' . $title . '</a></div>
								<div class="content " ' . $collapsedStyle . '>' . $description . '</div>
							</li>';

				$count++;
			}

			$navigationId = ($mainMenu) ? 'id="task-list"' : '';

			$content = '<ul ' . $navigationId . ' class="task-list">' . $content . '</ul>';

		}

		return $content;
	}

	/**
	 * Shows an overview list of available reports.
	 *
	 * @return	string	list of available reports
	 */
	protected function indexAction() {
		$content = '';
		$tasks = array();
		$icon = t3lib_extMgm::extRelPath('taskcenter') . 'task/task.gif';

			// render the tasks only if there are any available
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']) && count($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter']) > 0) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'] as $extKey => $extensionReports) {
				foreach ($extensionReports as $taskClass => $task) {
					if (!$this->checkAccess($extKey, $taskClass)) {
						continue;
					}
					$link = 'mod.php?M=user_task&SET[function]=' . $extKey . '.' . $taskClass;
					$taskTitle = $GLOBALS['LANG']->sL($task['title']);
					$taskDescriptionHtml = '';

						// Check for custom icon
					if (!empty($task['icon'])) {
						$icon = t3lib_div::getFileAbsFilename($task['icon']);
					}

					if (class_exists($taskClass)) {
						$taskInstance = t3lib_div::makeInstance($taskClass, $this);
						if ($taskInstance instanceof tx_taskcenter_Task) {
							$taskDescriptionHtml = $taskInstance->getOverview();
						}
					}

						// generate an array of all tasks
					$uniqueKey = $this->getUniqueKey($extKey . '.' . $taskClass);
					$tasks[$uniqueKey] = array(
						'title'				=> $taskTitle,
						'descriptionHtml'	=> $taskDescriptionHtml,
						'description'		=> $GLOBALS['LANG']->sL($task['description']),
						'icon'				=> $icon,
						'link'				=> $link,
						'uid'				=> $extKey . '.' . $taskClass
					);
				}
			}

			$content .= $this->renderListMenu($tasks, TRUE);
		} else {
			$flashMessage = t3lib_div::makeInstance(
				't3lib_FlashMessage',
				$GLOBALS['LANG']->getLL('no-tasks', TRUE),
				'',
				t3lib_FlashMessage::INFO
			);
			$this->content .= $flashMessage->render();
		}

		return $content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise
	 * perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'csh' => t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']),
			'shortcut' => '',
			'open_new_window' => $this->openInNewWindow()
		);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}

		return $buttons;
	}

	/**
	 * Check the access to a task. Considered are:
	 *  - Admins are always allowed
	 *  - Tasks can be restriced to admins only
	 *  - Tasks can be blinded for Users with TsConfig taskcenter.<extensionkey>.<taskName> = 0
	 *
	 * @param	string		$extKey: Extension key
	 * @param	string		$taskClass: Name of the task
	 * @return boolean		Access to the task allowed or not
	 */
	protected function checkAccess($extKey, $taskClass) {
			// check if task is blinded with TsConfig (taskcenter.<extkey>.<taskName>
		$tsConfig = $GLOBALS['BE_USER']->getTSConfig('taskcenter.' . $extKey . '.' . $taskClass);
		if (isset($tsConfig['value']) && intval($tsConfig['value']) == 0) {
			return FALSE;
		}

		// admins are always allowed
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		}

			// check if task is restricted to admins
		if (intval($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['taskcenter'][$extKey][$taskClass]['admin']) == 1) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Returns HTML code to dislay an url in an iframe at the right side of the taskcenter
	 *
	 * @param	string		$url: url to display
	 * @param	int		$max:
	 * @return	string		code that inserts the iframe (HTML)
	 */
	public function urlInIframe($url, $max=0) {
		$this->doc->JScodeArray[] =
		'function resizeIframe(frame,max) {
			var parent = $("typo3-docbody");
			var parentHeight = $(parent).getHeight() - 0;
			var parentWidth = $(parent).getWidth() - $("taskcenter-menu").getWidth() - 50;
			$("list_frame").setStyle({height: parentHeight+"px", width: parentWidth+"px"});

		}
		// event crashes IE6 so he is excluded first
		var version = parseFloat(navigator.appVersion.split(";")[1].strip().split(" ")[1]);
		if (!(Prototype.Browser.IE && version == 6)) {
			Event.observe(window, "resize", resizeIframe, false);
		}';

		return '<iframe onload="resizeIframe(this,' . $max . ');" scrolling="auto"  width="100%" src="' . $url . '" name="list_frame" id="list_frame" frameborder="no" style="margin-top:-51px;border: none;"></iframe>';
	}

	/**
	 * Create a unique key from a string which can be used in Prototype's Sortable
	 * Therefore '_' are replaced
	 *
	 * @param	string		$string: string which is used to generate the identifier
	 * @return	string		modified string
	 */
	protected function getUniqueKey($string) {
		$search		= array('.', '_');
		$replace	= array('-', '');

		return str_replace($search, $replace, $string);
	}

	/**
	 * This method prepares the link for opening the devlog in a new window
	 *
	 * @return	string	Hyperlink with icon and appropriate JavaScript
	 */
	protected function openInNewWindow() {
		$url = t3lib_div::getIndpEnv('TYPO3_REQUEST_SCRIPT');
		$onClick = "devlogWin=window.open('" . $url . "','taskcenter','width=790,status=0,menubar=1,resizable=1,location=0,scrollbars=1,toolbar=0');return false;";
		$content = '<a href="#" onclick="' . htmlspecialchars($onClick).'">' .
					'<img' . t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/open_in_new_window.gif', 'width="19" height="14"') . ' title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.openInNewWindow', 1) . '" class="absmiddle" alt="" />' .
					'</a>';
		return $content;
	}


}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/index.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/index.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_user_scoutnet_kalender_editor_index');

// Include files?
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}

$SOBE->main();
$SOBE->printContent();

?>
