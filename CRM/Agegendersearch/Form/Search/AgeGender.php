<?php

/**
 * A custom contact search
 */
class CRM_Agegendersearch_Form_Search_AgeGender extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(ts('Search by Age and Gender'));

    /**
     * Define the search form fields here
     */
    $form->add( 'text',
                'min_age',
                ts( 'Limit Age Between' ) );
    $form->add( 'text',
               'max_age',
               ts( '...and' ) );
 
    $gender = array('' => ts('- any gender -')) + 
                          CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'gender_id' );
    foreach ($gender as $key => $var) {
      $genderOptions[$key] = HTML_QuickForm::createElement('radio', null, ts('Gender'), $var, $key);
    }
    $form->addGroup($genderOptions, 'gender_id', ts('Gender'));
 
    /**
     * If you are using the sample template, this array tells the template fields to render
     * for the search form.
     */
    $form->assign( 'elements', array( 'min_age', 'max_age', 'gender_id') );

  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      ts('Name')         => 'sort_name',
      ts('Address')      => 'street_address',
      ts('City')         => 'city',
      ts('Postal Code')  => 'postal_code',
      ts('Gender')       => 'gender',
      ts('Age')          => 'age', 
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "    contact_a.id as contact_id,
                contact_a.sort_name as sort_name,
                ca.street_address as street_address,
                ca.postal_code as postal_code,
                ca.city as city,
                cov.label as gender,
                DATE_FORMAT( FROM_DAYS( TO_DAYS( NOW( ) ) - TO_DAYS( contact_a.birth_date ) ) , '%Y' ) +0 as age
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    return " 
           FROM civicrm_contact contact_a
      LEFT JOIN civicrm_address ca 
             ON (ca.contact_id = contact_a.id AND ca.is_primary = 1)
      LEFT JOIN civicrm_option_value cov 
             ON (cov.value = contact_a.gender_id AND cov.option_group_id =3 )
    ";
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $params = array();

    $count = 1;
    $clause = array();

    $clause[] = "contact_a.contact_type = 'Individual'";
 
    $minAge = CRM_Utils_Array::value('min_age',
      $this->_formValues
    );
    if ( $minAge != NULL ) {
      $params[$count] = array(date("Y-m-d", strtotime("now - {$minAge} year")), 'String');
      $clause[] = "contact_a.birth_date <= %{$count}";
      $count++;
    }
 
    $maxAge = CRM_Utils_Array::value('max_age',
      $this->_formValues
    );
    if ( $maxAge != NULL ) {
      $params[$count] = array(date("Y-m-d", strtotime("now - {$maxAge} year")), 'String');
      $clause[] = "contact_a.birth_date >= %{$count}";
      $count++;
    }
 
    $gender = CRM_Utils_Array::value( 'gender_id', 
      $this->_formValues 
    );
    if ($gender) {
      $params[$count] = array($gender, 'Integer');
      $clause[] = "contact_a.gender_id = %{$count}";
      $count++;
    }
 
    if (!empty($clause)) {
      $where = implode(' AND ', $clause);
    }

    return $this->whereClause($where, $params);
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    //$row['sort_name'] .= ' ( altered )';
  }
}
