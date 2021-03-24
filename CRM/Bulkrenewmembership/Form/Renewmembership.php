<?php

use CRM_Bulkrenewmembership_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Bulkrenewmembership_Form_Renewmembership extends CRM_Member_Form_Task {
  /**
     * The title of the group.
     *
     * @var string
     */
    protected $_title;

    /**
     * Maximum profile fields that will be displayed.
     * @var int
     */
    protected $_maxFields = 9;

    /**
     * Variable to store redirect path.
     * @var string
     */
    protected $_userContext;

    /**
     * Build all the data structures needed to build the form.
     *
     * @return void
     */
    public function preProcess() {
      // initialize the task and row fields
      parent::preProcess();

      //get the contact read only fields to display.
      $readOnlyFields = array_merge(['sort_name' => ts('Name')],
        CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
          'contact_autocomplete_options',
          TRUE, NULL, FALSE, 'name', TRUE
        )
      );
      //get the read only field data.
      $returnProperties = array_fill_keys(array_keys($readOnlyFields), 1);
      $contactDetails = CRM_Contact_BAO_Contact_Utils::contactDetails($this->_memberIds,
        'CiviMember', $returnProperties
      );
      $this->assign('contactDetails', $contactDetails);
      $this->assign('readOnlyFields', $readOnlyFields);
    }

    /**
     * Build the form object.
     *
     *
     * @return void
     */
    public function buildQuickForm() {
      $this->_title = ts('Bulk Renew Memberships');
      CRM_Utils_System::setTitle($this->_title);
      $this->addDefaultButtons(ts('Save'));
      $this->_fields = ['total_amount' => [
        'name' => 'total_amount',
        'title' => 'Total Amount',
        'html_type' => 'text',
      ]];
      // $this->_fields = CRM_Core_BAO_UFGroup::getFields(18, FALSE, CRM_Core_Action::VIEW);
      // print_r($this->_fields); die();
      // // remove file type field and then limit fields
      // $suppressFields = FALSE;
      // $removehtmlTypes = ['File'];
      // foreach ($this->_fields as $name => $field) {
      //   if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
      //     in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
      //   ) {
      //     $suppressFields = TRUE;
      //     unset($this->_fields[$name]);
      //   }
      //
      //   //fix to reduce size as we are using this field in grid
      //   if (is_array($field['attributes']) && !empty($this->_fields[$name]['attributes']['size']) && $this->_fields[$name]['attributes']['size'] > 19) {
      //     //shrink class to "form-text-medium"
      //     $this->_fields[$name]['attributes']['size'] = 19;
      //   }
      // }
      //
      // $this->_fields = array_slice($this->_fields, 0, $this->_maxFields);

      $this->addButtons([
        [
          'type' => 'submit',
          'name' => ts('Update Members(s)'),
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);

      $this->assign('profileTitle', $this->_title);
      $this->assign('componentIds', $this->_memberIds);

      //load all campaigns.
      // if (array_key_exists('member_campaign_id', $this->_fields)) {
      //   $this->_componentCampaigns = [];
      //   CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
      //     'CRM_Member_DAO_Membership',
      //     TRUE, 'campaign_id', 'id',
      //     ' id IN (' . implode(' , ', array_values($this->_memberIds)) . ' ) '
      //   );
      // }

      $customFields = CRM_Core_BAO_CustomField::getFields('Membership');
      foreach ($this->_memberIds as $memberId) {
        $typeId = CRM_Core_DAO::getFieldValue("CRM_Member_DAO_Membership", $memberId, 'membership_type_id');
        foreach ($this->_fields as $name => $field) {
          if ($customFieldID = CRM_Core_BAO_CustomField::getKeyID($name)) {
            $customValue = $customFields[$customFieldID] ?? NULL;
            $entityColumnValue = [];
            if (!empty($customValue['extends_entity_column_value'])) {
              $entityColumnValue = explode(CRM_Core_DAO::VALUE_SEPARATOR,
                $customValue['extends_entity_column_value']
              );
            }
            if ((CRM_Utils_Array::value($typeId, $entityColumnValue)) ||
              CRM_Utils_System::isNull($entityColumnValue[$typeId])
            ) {
              CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
            }
          }
          else {
            // print_r($field); die();
            // TODO add fields by mirroring how its done in commented out function
            $this->add($field['html_type'], $field['name'], $field['title'], NULL, FALSE);
            // handle non custom fields
            // CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
          }
        }
      }

      CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
      // print_r($this); die();
      $this->assign('fields', $this->_fields);

      // don't set the status message when form is submitted.
      $buttonName = $this->controller->getButtonName('submit');

      if ($suppressFields && $buttonName != '_qf_Batch_next') {
        CRM_Core_Session::setStatus(ts("File type field(s) in the selected profile are not supported for Update multiple memberships."), ts('Unsupported Field Type'), 'error');
      }

      $this->addDefaultButtons(ts('Update Memberships'));
    }

    /**
     * Set default values for the form.
     *
     *
     * @return void
     */
    public function setDefaultValues() {
      if (empty($this->_fields)) {
        return;
      }

      $defaults = [];
      foreach ($this->_memberIds as $memberId) {
        CRM_Core_BAO_UFGroup::setProfileDefaults(NULL, $this->_fields, $defaults, FALSE, $memberId, 'Membership');
      }

      return $defaults;
    }

    /**
     * Process the form after the input has been submitted and validated.
     *
     * @throws \CRM_Core_Exception
     * @throws \CiviCRM_API3_Exception
     */
    public function postProcess() {
      $params = $this->exportValues();
      $today = date("F j, Y, g:i a");

      if (!empty($this->_memberIds)) {
        foreach ($this->_memberIds as $key => $membershipId) {
          // get last membership payment
          $lastPaymentInfo = bulkrenewmembership_helperApiCall('MembershipPayment', 'get', [
            'sequential' => 1,
            'membership_id' => $membershipId,
            'options' => ['sort' => "id DESC"],
            'api.Contribution.getsingle' => ['id' => "\$value.contribution_id"],
          ]);

          // TODO create pending membership payment based on last membership payment
          if ($lastPaymentInfo['is_error'] == 0 && !empty($lastPaymentInfo['values'][0]['api.Contribution.getsingle']['id'])) {
            $newPaymentDetails = [
              'contribution_source' => "Bulk Renewal - $today",
              'contribution_status_id' => 'Pending',
              'is_pay_later' => 1,
              'receive_date' => $today,
            ];
            $paramsToCopy = [
              'contact_id',
              'currency',
              'total_amount',
              'financial_type_id',
              'financial_type',
              'contribution_type_id',
            ];
            foreach ($paramsToCopy as $key => $fieldName) {
              if (!empty($lastPaymentInfo['values'][0]['api.Contribution.getsingle'][$fieldName])) {
                $newPaymentDetails[$fieldName] = $lastPaymentInfo['values'][0]['api.Contribution.getsingle'][$fieldName];
              }
            }
            // Create new Membership Payment
            $renewPaymentDetails = bulkrenewmembership_helperApiCall('Contribution', 'create', $newPaymentDetails);
            if (isset($renewPaymentDetails['id'])) {
              // Create Connection between membership payment and contribution
              bulkrenewmembership_helperApiCall('MembershipPayment', 'create', ['contribution_id' => $renewPaymentDetails['id'], 'membership_id' => $membershipId]);
            }
          }
        }
      }

      // TODO make more accurate message
      if (isset($params['field'])) {
        $this->submit($params);
        CRM_Core_Session::setStatus(ts('Your updates have been saved.'), ts('Saved'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(ts('No updates have been saved.'), ts('Not Saved'), 'alert');
      }
    }

    // /**
    //  * @param array $params
    //  *
    //  * @return mixed
    //  * @throws \CRM_Core_Exception
    //  * @throws \CiviCRM_API3_Exception
    //  */
    // public function submit(array $params) {
    //   $dates = [
    //     'membership_join_date',
    //     'membership_start_date',
    //     'membership_end_date',
    //   ];
    //   $customFields = [];
    //   foreach ($params['field'] as $key => $value) {
    //     $value['id'] = $key;
    //     if (!empty($value['membership_source'])) {
    //       $value['source'] = $value['membership_source'];
    //     }
    //
    //     if (!empty($value['membership_type'])) {
    //       $membershipTypeId = $value['membership_type_id'] = $value['membership_type'][1];
    //     }
    //
    //     unset($value['membership_source']);
    //     unset($value['membership_type']);
    //
    //     //Get the membership status
    //     $value['status_id'] = (CRM_Utils_Array::value('membership_status', $value)) ? $value['membership_status'] : CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $key, 'status_id');
    //     unset($value['membership_status']);
    //     foreach ($dates as $val) {
    //       if (isset($value[$val])) {
    //         $value[$val] = CRM_Utils_Date::processDate($value[$val]);
    //       }
    //     }
    //     if (empty($customFields)) {
    //       if (empty($value['membership_type_id'])) {
    //         $membershipTypeId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $key, 'membership_type_id');
    //       }
    //
    //       // membership type custom data
    //       $customFields = CRM_Core_BAO_CustomField::getFields('Membership', FALSE, FALSE, $membershipTypeId);
    //
    //       $customFields = CRM_Utils_Array::crmArrayMerge($customFields,
    //         CRM_Core_BAO_CustomField::getFields('Membership',
    //           FALSE, FALSE, NULL, NULL, TRUE
    //         )
    //       );
    //     }
    //     //check for custom data
    //     $value['custom'] = CRM_Core_BAO_CustomField::postProcess($params['field'][$key],
    //       $key,
    //       'Membership',
    //       $membershipTypeId
    //     );
    //
    //     $membership = CRM_Member_BAO_Membership::add($value);
    //
    //     // add custom field values
    //     if (!empty($value['custom']) &&
    //       is_array($value['custom'])
    //     ) {
    //       CRM_Core_BAO_CustomValueTable::store($value['custom'], 'civicrm_membership', $membership->id);
    //     }
    //   }
    //   return $value;
    // }

  }
