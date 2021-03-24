<?php

require_once 'bulkrenewmembership.civix.php';
// phpcs:disable
use CRM_Bulkrenewmembership_ExtensionUtil as E;
// phpcs:enable

/**
 * Implements hook_civicrm_searchTasks().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_searchTasks/
 */
function bulkrenewmembership_civicrm_searchTasks($objectType, &$tasks) {
  // print_r($tasks); die();
  // Add new task to membership search
  if ($objectType == 'membership') {
    $tasks[] = [
      'title' => 'Bulk Update Memberships',
      'class' => 'CRM_Bulkrenewmembership_Form_Renewmembership',
      // 'class' => ['CRM_Member_Form_Task_PickProfile', 'CRM_Bulkrenewmembership_Form_Renewmembership'],
      'result' => 1,
    ];
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function bulkrenewmembership_civicrm_config(&$config) {
  _bulkrenewmembership_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function bulkrenewmembership_civicrm_xmlMenu(&$files) {
  _bulkrenewmembership_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function bulkrenewmembership_civicrm_install() {
  _bulkrenewmembership_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function bulkrenewmembership_civicrm_postInstall() {
  _bulkrenewmembership_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function bulkrenewmembership_civicrm_uninstall() {
  _bulkrenewmembership_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function bulkrenewmembership_civicrm_enable() {
  _bulkrenewmembership_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function bulkrenewmembership_civicrm_disable() {
  _bulkrenewmembership_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function bulkrenewmembership_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _bulkrenewmembership_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function bulkrenewmembership_civicrm_managed(&$entities) {
  _bulkrenewmembership_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function bulkrenewmembership_civicrm_caseTypes(&$caseTypes) {
  _bulkrenewmembership_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function bulkrenewmembership_civicrm_angularModules(&$angularModules) {
  _bulkrenewmembership_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function bulkrenewmembership_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _bulkrenewmembership_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function bulkrenewmembership_civicrm_entityTypes(&$entityTypes) {
  _bulkrenewmembership_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_thems().
 */
function bulkrenewmembership_civicrm_themes(&$themes) {
  _bulkrenewmembership_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function bulkrenewmembership_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function bulkrenewmembership_civicrm_navigationMenu(&$menu) {
//  _bulkrenewmembership_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _bulkrenewmembership_civix_navigationMenu($menu);
//}
