<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}
/*
  class Invitation extends SeedObject
  {

  public $table_element = 'quest_invitation';
  public $element = 'invitation';

  public function __construct($db)
  {
  global $conf, $langs;

  $this->db = $db;

  $this->fields = array(
  'fk_questionnaire' => array('type' => 'integer', 'index' => true)
  , 'date_limite_reponse' => array('type' => 'date')
  );

  $this->init();

  $this->entity = $conf->entity;
  }

  public function load($id, $ref = null, $loadChild = true)
  {
  global $db;

  $res = parent::fetchCommon($id, $ref);

  if ($loadChild)
  $this->fetchObjectLinked();

  return $res;
  }

  public function save()
  {

  global $user;

  return $this->id > 0 ? $this->updateCommon($user) : $this->createCommon($user);
  }

  public function delete(User &$user)
  {

  if (empty($this->invitations_user))
  $this->loadInvitationsUser();
  if (!empty($this->invitations_user))
  {
  foreach ($this->invitations_user as &$invitation_user)
  $invitation_user->delete($user);
  }

  parent::deleteCommon($user);
  }

  function loadInvitationsUser()
  {

  global $db;

  $inv_user = new InvitationUser($db);

  $sql = 'SELECT rowid
  FROM '.MAIN_DB_PREFIX.$inv_user->table_element.'
  WHERE fk_invitation = '.$this->id;
  $resql = $db->query($sql);
  if (!empty($resql) && $db->num_rows($resql) > 0)
  {
  $this->invitations_user = array();

  while ($res = $db->fetch_object($resql))
  {
  $inv_user = new InvitationUser($db);
  $inv_user->load($res->rowid);
  $this->invitations_user[] = $inv_user;
  }
  }
  else
  return 0;

  return 1;
  }

  function addInvitationsUser(&$groups, &$users, $emails)
  {

  require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
  dol_include_once('/questionnaire/class/questionnaire.class.php');
  global $db;

  $questionnaire = new Questionnaire($db);
  $questionnaire->load($this->fk_questionnaire);
  list($alreadyInvitedFKElements, $alreadyInvitedEmails) = $questionnaire->getAlreadyInvitedUsers();

  $all_users = array();
  $user = new User($db);
  if (!empty($groups))
  {
  require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
  foreach ($groups as $id_grp)
  {
  // On crée l'invitation pour le groupe
  $invitation_user = new InvitationUser($db);
  $invitation_user->fk_invitation = $this->id;
  $invitation_user->fk_usergroup = $id_grp;
  $invitation_user->save();

  // On récupère les utilisateurs du groupe pour leur créer une invitation également (seul celle-ci compte vraiment)
  $grp = new UserGroup($db);
  $grp->fetch($id_grp);
  $group_users = $grp->listUsersForGroup();
  if (!empty($group_users))
  {
  foreach ($group_users as &$usr)
  if (!in_array($usr->id, $alreadyInvitedFKElements))
  $all_users[] = $usr->id;
  }
  }
  }

  if (!empty($users))
  {

  foreach ($users as $id_user)
  if (!in_array($id_user, $alreadyInvitedFKElements))
  $all_users[] = $id_user;
  }

  $all_users = array_unique($all_users);

  if (!empty($emails))
  {
  if (strpos($emails, ',') !== false)
  $emails = explode(',', $emails);
  else
  $emails = array($emails);
  }

  foreach ($all_users as $id_usr)
  {

  $invitation_user = new InvitationUser($db);
  $invitation_user->fk_invitation = $this->id;
  $user->fetch($id_usr);
  $invitation_user->email = $user->email;
  $invitation_user->fk_user = $id_usr;
  $invitation_user->token = bin2hex(random_bytes(16));
  $invitation_user->save();
  }

  foreach ($emails as $email)
  {
  if (in_array($email, $alreadyInvitedEmails))
  continue;
  $invitation_user = new InvitationUser($db);
  $invitation_user->fk_invitation = $this->id;
  $invitation_user->fk_user = 0;
  $invitation_user->email = $email;
  $invitation_user->token = bin2hex(random_bytes(16));
  $invitation_user->save();
  }
  }

  function delInvitationsUser(&$groups, &$users, $emails)
  {

  global $db, $user;

  $invitation_user = new InvitationUser($db);

  $sql = 'SELECT rowid, email, fk_user, fk_usergroup
  FROM '.MAIN_DB_PREFIX.$invitation_user->table_element.'
  WHERE fk_invitation = '.$this->id;

  $resql = $db->query($sql);
  if (!empty($resql) && $db->num_rows($resql) > 0)
  {
  while ($res = $db->fetch_object($resql))
  {
  if (strpos($emails, $res->email) === false && !in_array($res->fk_user, $users) && !in_array($res->fk_usergroup, $groups))
  {
  $invitation_user = new InvitationUser($db);
  $invitation_user->load($res->rowid);
  $invitation_user->delete($user);
  }
  }
  }
  }

  }
 */

class InvitationUser extends SeedObject
{

	public $table_element = 'quest_invitation_user';
	public $element = 'invitation_user';
	public $fk_element;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Closed status
	 */
	const STATUS_CLOSED = 2;

	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->fields = array(
			'fk_questionnaire' => array('type' => 'integer', 'index' => true)
			, 'fk_element' => array('type' => 'integer', 'index' => true)
			, 'type_element' => array('type' => 'string')
			, 'fk_usergroup' => array('type' => 'integer', 'index' => true)
			, 'token' => array('type' => 'string')
			, 'email' => array('type' => 'string')
			, 'fk_statut' => array('type' => 'integer', 'index' => true) // Indique si l'utilisateur a enregistré ses données pour terminer plus tard, ou s'il a terminé et validé son questionnaire
			, 'sent' => array('type' => 'integer', 'index' => true) // Indique si l'utilisateur a enregistré ses données pour terminer plus tard, ou s'il a terminé et validé son questionnaire
			, 'date_limite_reponse' => array('type' => 'date')
		);

		$this->init();

		$this->entity = $conf->entity;
	}

	public function load($id, $ref = null, $loadChild = true)
	{
		global $db;

		$res = parent::fetchCommon($id, $ref);

		if ($loadChild)
			$this->fetchObjectLinked();

		return $res;
	}

	public function loadBy($TFieldValue, $annexe = false)
	{
		global $db;

		$sql = 'SELECT rowid
				FROM '.MAIN_DB_PREFIX.$this->table_element.'
				WHERE 1';
		foreach ($TFieldValue as $field => $val)
			$sql .= ' AND '.$field.' = '.$val;

		$resql = $db->query($sql);
		if (!empty($resql) && $db->num_rows($resql) > 0)
		{
			$res = $db->fetch_object($resql);
			$res = $this->load($res->rowid);
		}

		return $res;
	}

	function setValid()
	{

		$this->fk_statut = 1;
		$this->save();
	}

	public function save()
	{

		global $user;

		return $this->id > 0 ? $this->updateCommon($user) : $this->createCommon($user);
	}

	public function delete(User &$user)
	{

		parent::deleteCommon($user);
	}

	public static function LibStatut($status, $mode)
	{
		global $langs, $questionnaire_status_forced_key;

		$langs->load('questionnaire@questionnaire');

		if ($status == self::STATUS_DRAFT)
		{
			$statustrans = 'statut0';
			$keytrans = 'questionnaireStatusNotSent';
			$shortkeytrans = 'NotSent';
		}
		if ($status == self::STATUS_VALIDATED)
		{
			$statustrans = 'statut1';
			$keytrans = 'questionnaireStatusSent';
			$shortkeytrans = 'Sent';
		}


		if ($mode == 0)
			return img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 1)
			return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($keytrans);
		elseif ($mode == 2)
			return $langs->trans($keytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 3)
			return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($shortkeytrans);
		elseif ($mode == 4)
			return $langs->trans($shortkeytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 5)
			return '<span class="hideonsmartphone">'.$this->labelstatut_short[$statut].' </span>'.img_picto($this->labelstatut[$statut], $statuttrans);
		// mode 6 used by dol_banner() function

		elseif ($mode == 6)
			return '<span class="hideonsmartphone">'.$langs->trans(empty($questionnaire_status_forced_key) ? $keytrans : $questionnaire_status_forced_key).' </span>'.img_picto($langs->trans(empty($questionnaire_status_forced_key) ? $keytrans : $questionnaire_status_forced_key), $statustrans);
	}

	function addInvitationsUser(&$groups, &$users, $emails,$selectedByTarget=null)
	{

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		dol_include_once('/questionnaire/class/questionnaire.class.php');
		global $db;

		$questionnaire = new Questionnaire($db);
		$questionnaire->load($this->fk_questionnaire);
		list($alreadyInvitedFKElements, $alreadyInvitedEmails) = $questionnaire->getAlreadyInvitedElements();

		$all_users = array();
		$user = new User($db);
		if (!empty($groups))
		{
			require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
			foreach ($groups as $id_grp)
			{



				// On récupère les utilisateurs du groupe pour leur créer une invitation également (seul celle-ci compte vraiment)
				$grp = new UserGroup($db);
				$grp->fetch($id_grp);
				$group_users = $grp->listUsersForGroup();
				if (!empty($group_users))
				{
					foreach ($group_users as &$usr)
					{
						if (in_array($usr->id, $alreadyInvitedFKElements['user']))
							continue;
						else
						{
							$invitation_user = new InvitationUser($db);
							$invitation_user->fk_questionnaire = $this->fk_questionnaire;
							$invitation_user->date_limite_reponse = $this->date_limite_reponse;
							$invitation_user->fk_usergroup = $id_grp;
							$invitation_user->email = $usr->email;
							$invitation_user->fk_element = $usr->id;
							$invitation_user->type_element = 'user';
							$invitation_user->token = bin2hex(openssl_random_pseudo_bytes(16)); // When we'll pass to php7 use random_bytes
							$invitation_user->save();
						}
					}
				}
			}
		}
		list($alreadyInvitedFKElements, $alreadyInvitedEmails) = $questionnaire->getAlreadyInvitedElements();

		if (!empty($users))
		{

			foreach ($users as $id_user)
				if (!in_array($id_user, $alreadyInvitedFKElements['user']))
					$all_users[] = $id_user;
		}

		$all_users = array_unique($all_users);

		if (!empty($emails))
		{
			if (strpos($emails, ',') !== false)
				$emails = explode(',', $emails);
			else
				$emails = array($emails);
		}
		if (!empty($all_users))
		{
			foreach ($all_users as $id_usr)
			{

				$invitation_user = new InvitationUser($db);
				$invitation_user->fk_questionnaire = $this->fk_questionnaire;
				$invitation_user->date_limite_reponse = $this->date_limite_reponse;
				$user->fetch($id_usr);
				$invitation_user->email = $user->email;
				$invitation_user->fk_element = $id_usr;
				$invitation_user->type_element = 'user';
				$invitation_user->token = bin2hex(openssl_random_pseudo_bytes(16));
				$invitation_user->save();
			}
		}
		if (!empty($emails))
		{
			foreach ($emails as $email)
			{
				if (in_array($email, $alreadyInvitedEmails))
					continue;
				$invitation_user = new InvitationUser($db);
				$invitation_user->fk_questionnaire = $this->fk_questionnaire;
				$invitation_user->date_limite_reponse = $this->date_limite_reponse;
				$invitation_user->fk_element = 0;
				$invitation_user->email = $email;
				$invitation_user->token = bin2hex(openssl_random_pseudo_bytes(16));
				$invitation_user->save();
			}
		}
		
		if (!empty($selectedByTarget))
		{
			foreach ($selectedByTarget as $email => $selected)
			{
				if ('thirdparty'==$selected['source_type'] && !empty($alreadyInvitedFKElements['thirdparty'])&& in_array($selected['source_id'], $alreadyInvitedFKElements['thirdparty']))
					continue;
				else if ('contact'==$selected['source_type'] &&  !empty($alreadyInvitedFKElements['contact'])&& in_array($selected['source_id'], $alreadyInvitedFKElements['contact']))
					continue;
				else
				{
					$invitation_user = new InvitationUser($db);
					$invitation_user->fk_questionnaire = $this->fk_questionnaire;
					$invitation_user->date_limite_reponse = $this->date_limite_reponse;
					$invitation_user->fk_usergroup = 0;
					$invitation_user->email = $email;
					$invitation_user->fk_element = $selected['source_id'];
					$invitation_user->type_element = $selected['source_type'];
					$invitation_user->token = bin2hex(openssl_random_pseudo_bytes(16)); // When we'll pass to php7 use random_bytes
					$invitation_user->save();
					
					
				}
			}
		}
	}

	function reopen()
	{
		$this->fk_statut = 0;
		$this->save();
	}
	
	function getFk_element(){
		
		
		return $this->fk_element;
	}
	
	
	/**
	 *    This is the main function that returns the array of emails
	 *
	 *    @param	int		$mailing_id    	Id of mailing. No need to use it.
	 *    @param	array	$socid  		Array of id soc to add
	 *    @param	int		$type_of_target	Defined in advtargetemailing.class.php
	 *    @param	array	$contactid 		Array of contact id to add
	 *    @return   int 					<0 if error, number of emails added if ok
	 */
	function add_to_target_spec($mailing_id,$socid,$type_of_target, $contactid)
	{
		global $conf, $langs;

		dol_syslog(get_class($this)."::add_to_target socid=".var_export($socid,true).' contactid='.var_export($contactid,true));

		$cibles = array();

		if (($type_of_target==1) || ($type_of_target==3)) {
			// Select the third parties from category
			if (count($socid)>0)
			{
				$sql= "SELECT s.rowid as id, s.email as email, s.nom as name, null as fk_contact";
				$sql.= " FROM ".MAIN_DB_PREFIX."societe as s LEFT OUTER JOIN ".MAIN_DB_PREFIX."societe_extrafields se ON se.fk_object=s.rowid";
				$sql.= " WHERE s.entity IN (".getEntity('societe').")";
				$sql.= " AND s.rowid IN (".implode(',',$socid).")";
				$sql.= " ORDER BY email";

    			// Stock recipients emails into targets table
    			$result=$this->db->query($sql);
    			if ($result)
    			{
    				$num = $this->db->num_rows($result);
    				$i = 0;

    				dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found", LOG_DEBUG);

    				$old = '';
    				while ($i < $num)
    				{
    					$obj = $this->db->fetch_object($result);

    					if (!empty($obj->email) && filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
    						if (!array_key_exists($obj->email, $cibles)) {
    							$cibles[$obj->email] = array(
    								'email' => $obj->email,
    								'fk_contact' => $obj->fk_contact,
    								'name' => $obj->name,
    								'firstname' => $obj->firstname,
    								'other' => '',
    								'source_url' => $this->url($obj->id,'thirdparty'),
    								'source_id' => $obj->id,
    								'source_type' => 'thirdparty'
    							);
    						}
    					}

    					$i++;
    				}
    			}
    			else
    			{
    				dol_syslog($this->db->error());
    				$this->error=$this->db->error();
    				return -1;
    			}
			}
		}

		if  (($type_of_target==1) || ($type_of_target==2) || ($type_of_target==4)) {
			// Select the third parties from category
			if (count($socid)>0 || count($contactid)>0)
			{
				$sql= "SELECT socp.rowid as id, socp.email as email, socp.lastname as lastname, socp.firstname as firstname";
				$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as socp";
				$sql.= " WHERE socp.entity IN (".getEntity('societe').")";
				if (count($contactid)>0) {
					$sql.= " AND socp.rowid IN (".implode(',',$contactid).")";
				}
				if (count($socid)>0) {
					$sql.= " AND socp.fk_soc IN (".implode(',',$socid).")";
				}
				$sql.= " ORDER BY email";

    			// Stock recipients emails into targets table
    			$result=$this->db->query($sql);
    			if ($result)
    			{
    				$num = $this->db->num_rows($result);
    				$i = 0;

    				dol_syslog(get_class($this)."::add_to_target mailing ".$num." targets found");

    				$old = '';
    				while ($i < $num)
    				{
    					$obj = $this->db->fetch_object($result);

    					if (!empty($obj->email) && filter_var($obj->email, FILTER_VALIDATE_EMAIL)) {
    						if (!array_key_exists($obj->email, $cibles)) {
    							$cibles[$obj->email] = array(
    								'email' => $obj->email,
    								'fk_contact' =>$obj->id,
    								'lastname' => $obj->lastname,
    								'firstname' => $obj->firstname,
    								'other' => '',
    								'source_url' => $this->url($obj->id,'contact'),
    								'source_id' => $obj->id,
    								'source_type' => 'contact'
    							);
    						}
    					}

    					$i++;
    				}
    			}
    			else
    			{
    				dol_syslog($this->db->error());
    				$this->error=$this->db->error();
    				return -1;
    			}
			}
		}

		return $cibles;
	}
	
	/**
	 *  Can include an URL link on each record provided by selector shown on target page.
	 *
	 *  @param	int		$id		ID
	 *  @param	string		$type	type
	 *  @return string      	Url link
	 */
	function url($id,$type)
	{
		if ($type=='thirdparty') {
			$companystatic=new Societe($this->db);
			$companystatic->fetch($id);
			return $companystatic->getNomUrl(0, '', 0, 1);
		} elseif ($type=='contact') {
			$contactstatic=new Contact($this->db);
			$contactstatic->fetch($id);
			return $contactstatic->getNomUrl(0, '', 0, '', -1, 0);
		}
	}
	

}
