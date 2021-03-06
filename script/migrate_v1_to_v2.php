<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

chdir(__DIR__);
define('INC_FROM_CRON_SCRIPT', true);
    
require '../config.php';
dol_include_once('questionnaire/class/questionnaire.class.php');
dol_include_once('questionnaire/class/question.class.php');
dol_include_once('questionnaire/class/invitation.class.php');

       
global $db;

$sql = "SELECT DISTINCT fk_questionnaire FROM ".MAIN_DB_PREFIX."quest_question WHERE fk_questionnaire != 0";

$resql = $db->query($sql);
while($obj = $db->fetch_object($resql)){
	
	$questionnaire = new Questionnaire($db);
	$questionnaire->load($obj->fk_questionnaire);
	$questionnaire->loadQuestions();
	foreach($questionnaire->questions as $key => $question){
		$question->rang = $key;
		$question->save(1);
	}
	
	
}

	
$sql = 'UPDATE '.MAIN_DB_PREFIX.'quest_invitation_user SET fk_element = fk_user, type_element="user"
		WHERE fk_user !=0';

$db->query($sql);


$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."quest_invitation_user";

$resql = $db->query($sql);
while($obj = $db->fetch_object($resql)){
	
	$invUser = new InvitationUser($db);
	$invUser->load($obj->rowid);
	$invUser->ref = $invUser->getNumero();
	$invUser->save();
}
