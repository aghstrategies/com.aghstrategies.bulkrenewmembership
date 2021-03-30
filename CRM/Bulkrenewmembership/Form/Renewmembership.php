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
      $readOnlyFields = array_merge([
        'sort_name' => ts('Name'),
        'membership_id' => ts('Membership ID'),
        'membership_name' => ts('Membership Type'),
        'contribution_source' => ts('Contribution Source'),
        'pending_payment' => ts('Has Pending Payment?'),
      ],
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
      foreach ($contactDetails as $memberId => &$contactInfo) {
        $contactInfo['membership_id'] = $memberId;
        $membership = bulkrenewmembership_helperApiCall('Membership', 'getsingle', ['id' => $memberId]);
        $contactInfo['membership_name'] = $membership['membership_name'];
        $memPayment = $this->fetch_lastpaymentinfo($memberId, 'Completed');
        if (empty($memPayment)) {
          unset($contactDetails[$memberId]);
          $memberIds = $this->getVar('_memberIds');
          if (($key = array_search($memberId, $memberIds)) !== false) {
            unset($memberIds[$key]);
          }
          $this->setVar('_memberIds', $memberIds);
          CRM_Core_Session::setStatus(ts('Removing member Id %1 from bulk update screen because no completed related membership payment to copy was found.', [1 => $memberId]), ts('No Membership Payment Found'), 'alert');
        }
        $pendingPayment = $this->fetch_lastpaymentinfo($memberId, 'Pending');
        if (!empty($pendingPayment['contribution_id'])) {
          $contactInfo['pending_payment'] = "ID: {$pendingPayment['contribution_id']}";
          if (!empty($pendingPayment['contribution_source'])) {
            $contactInfo['pending_payment'] .= ", SOURCE: {$pendingPayment['contribution_source']}";
          }
        }
        $contactInfo['contribution_source'] = $memPayment['contribution_source'];
      }
      // print_r($this); die();
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
      $this->_fields = [
        'financial_type' => [
          'name' => 'financial_type',
          'title' => 'Financial Type',
          'html_type' => 'select',
          'attributes' => [],
          'required' => FALSE,
        ],
        'total_amount' => [
          'name' => 'total_amount',
          'title' => 'Total Amount',
          'html_type' => 'text',
        ],
        'is_renew' => [
          'name' => 'is_renew',
          'title' => 'Confirm',
          'html_type' => 'select',
          'attributes' => [],
          'required' => FALSE,
        ],
      ];

      // // remove file type field and then limit fields
      $suppressFields = FALSE;
      $removehtmlTypes = ['File'];
      foreach ($this->_fields as $name => $field) {
        if ($cfID = CRM_Core_BAO_CustomField::getKeyID($name) &&
          in_array($this->_fields[$name]['html_type'], $removehtmlTypes)
        ) {
          $suppressFields = TRUE;
          unset($this->_fields[$name]);
        }

        //fix to reduce size as we are using this field in grid
        if (is_array($field['attributes']) && !empty($this->_fields[$name]['attributes']['size']) && $this->_fields[$name]['attributes']['size'] > 19) {
          //shrink class to "form-text-medium"
          $this->_fields[$name]['attributes']['size'] = 19;
        }
      }

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
      if (array_key_exists('member_campaign_id', $this->_fields)) {
        $this->_componentCampaigns = [];
        CRM_Core_PseudoConstant::populate($this->_componentCampaigns,
          'CRM_Member_DAO_Membership',
          TRUE, 'campaign_id', 'id',
          ' id IN (' . implode(' , ', array_values($this->_memberIds)) . ' ) '
        );
      }

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
            // handle non custom fields
            CRM_Core_BAO_UFGroup::buildProfile($this, $field, NULL, $memberId);
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

        // Populate Membership Contribution Fields
        $lastPaymentInfo = $this->fetch_lastpaymentinfo($memberId, 'Completed');
        $pendingPaymentInfo = $this->fetch_lastpaymentinfo($memberId, 'Pending');

        foreach ($this->_fields as $fieldName => $fieldDetails) {

          // If there is a pending payment already set Renew to unchecked
          if ($fieldName == 'is_renew') {
            if (!empty($pendingPaymentInfo)) {
              $defaults["field[$memberId][{$fieldDetails['name']}]"] = 0;
            }
            else {
              $defaults["field[$memberId][{$fieldDetails['name']}]"] = 1;
            }
          }
          elseif ($fieldName == 'financial_type') {
            $defaults["field[$memberId][{$fieldDetails['name']}]"] = $lastPaymentInfo['financial_type_id'];
          }
          elseif (isset($lastPaymentInfo[$fieldDetails['name']])) {
            $defaults["field[$memberId][{$fieldDetails['name']}]"] = $lastPaymentInfo[$fieldDetails['name']];
          }
        }
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
          $somethingWentWrong = 0;

          // create pending membership payment based on last membership payment
          if ($params['field'][$membershipId]['is_renew'] == 1) {
            $lastPaymentInfo = $this->fetch_lastpaymentinfo($membershipId, 'Completed');
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
              if (!empty($lastPaymentInfo[$fieldName])) {
                $newPaymentDetails[$fieldName] = $lastPaymentInfo[$fieldName];
              }
            }
            foreach ($this->_fields as $fieldName => $fieldDetails) {
              if (isset($fieldDetails['name']) && isset($this->_submitValues['field'][$membershipId][$fieldDetails['name']])) {
                $newPaymentDetails[$fieldDetails['name']] = $this->_submitValues['field'][$membershipId][$fieldDetails['name']];
              }
            }
            if (isset($this->_submitValues['field'][$membershipId]['financial_type'])) {
              $newPaymentDetails['financial_type_id'] = $newPaymentDetails['contribution_type_id'] = $this->_submitValues['field'][$membershipId]['financial_type'];
            }

            // Create new Membership Payment
            $renewPaymentDetails = bulkrenewmembership_helperApiCall('Contribution', 'create', $newPaymentDetails);
            if (isset($renewPaymentDetails['id'])) {
              // Create Connection between membership payment and contribution
              $memPayment = bulkrenewmembership_helperApiCall('MembershipPayment', 'create', ['contribution_id' => $renewPaymentDetails['id'], 'membership_id' => $membershipId]);
              if ($memPayment['is_error'] == 1) {
                $somethingWentWrong = 1;
              }
            }
            else {
              $somethingWentWrong = 1;
            }
          }
        }
        if ($somethingWentWrong == 1) {
          CRM_Core_Session::setStatus(ts('Error creating payment for Membership ID .', [1 => $membershipId]), ts('Not Saved'), 'alert');
        }
      }
      $this->submit($params);
    }

    function fetch_lastpaymentinfo($membershipId, $status) {
      $lastPayment = [];
      $lastPaymentInfo = bulkrenewmembership_helperApiCall('MembershipPayment', 'get', [
        'sequential' => 1,
        'contribution_id.contribution_status_id' => $status,
        'membership_id' => $membershipId,
        'options' => ['sort' => "id DESC"],
        'api.Contribution.getsingle' => ['id' => "\$value.contribution_id"],
      ]);
      if ($lastPaymentInfo['is_error'] == 0 && !empty($lastPaymentInfo['values'][0]['api.Contribution.getsingle']['id'])) {
        $lastPayment = $lastPaymentInfo['values'][0]['api.Contribution.getsingle'];
      }
      return $lastPayment;
    }

    public function submit(array $params) {
      return $params;
    }

  }
