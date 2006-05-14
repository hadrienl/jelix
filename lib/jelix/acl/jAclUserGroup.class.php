<?php
/**
* @package     jelix
* @subpackage  acl
* @version     $Id:$
* @author      Laurent Jouanneau
* @contributor
* @copyright   2006 Laurent Jouanneau
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 * classe pour g�rer les groupes d'utilisateur et gerer les utilisateurs inscrits dans
 * le syst�me de droits
 */
class jAclUserGroup {

    function getUsersList($groupid){
      $dao = jDao::get('acl~jaclusergroup');
      return $dao->getUsersGroup($groupid);
    }

    function createUser($login, $defaultGroup=true){
      $daousergroup = jDao::get('acl~jaclusergroup');
      $daogroup = jDao::get('acl~jaclgroup');
      $usergrp = jDao::createRecord('acl~jaclusergroup');
      $usergrp->login =$login;

      // si $defaultGroup -> assign le user aux groupes par defaut
      if($defaultGroup){
         $defgrp = $daogroup->getDefaultGroups();
         foreach($defgrp as $group){
            $usergrp->id_aclgrp = $group->id_aclgrp;
            $daousergroup->insert($usergrp);
         }
      }

      // creation d'un groupe personnel
      $persgrp = jDao::createRecord('acl~jaclgroup');
      $persgrp->name = $login;
      $persgrp->grouptype = 2;
      $persgrp->ownerlogin = $login;

      $daogroup->insert($persgrp);
      $usergrp->id_aclgrp = $persgrp->id_aclgrp;
      $daousergroup->insert($usergrp);
    }


    function addUserToGroup($login, $groupid){
      $daousergroup = jDao::get('acl~jaclusergroup');
      $usergrp = jDao::createRecord('acl~jaclusergroup');
      $usergrp->login =$login;
      $usergrp->id_aclgrp = $groupid;
      $daousergroup->insert($usergrp);
    }

    function removeUserFromGroup($login,$groupid){
      $daousergroup = jDao::get('acl~jaclusergroup');
      $daousergroup->del($login,$groupid);
    }

    function removeUser($login){
      $daogroup = jDao::get('acl~jaclgroup');
      $daoright = jDao::get('acl~jaclrights');
      $daousergroup = jDao::get('acl~jaclusergroup');

      // recupere le groupe priv�
      $privategrp = $daogroup->getPrivateGroup($login);
      if(!$privategrp) return;

      // supprime les droits sur le groupe priv� (jacl_rights)
      $daoright->deleteByGroup($privategrp->id_aclgrp);

      // supprime le groupe personnel du user (jacl_group)
      $daogroup->del($privategrp->id_aclgrp);

      // l'enleve de tous les groupes (jacl_users_group)
      $daousergroup->deleteByUser($login);
    }

     // renvoi group id
    function createGroup($name){
        $group = jDao::createRecord('acl~jaclgroup');
        $group->name=$name;
        $group->grouptype=0;
        $daogroup = jDao::get('acl~jaclgroup');
        $daogroup->insert($group);
        return $group->id_aclgrp;
    }

    function setDefaultGroup($groupid, $default=true){
       $daogroup = jDao::get('acl~jaclgroup');
       if($default)
         $daogroup->setToDefault($groupid);
       else
         $daogroup->setToNormal($groupid);
    }

    function updateGroup($groupid, $name){
       $daogroup = jDao::get('acl~jaclgroup');
       $daogroup->changeName($groupid,$name);
    }

    function removeGroup($groupid){
       $daogroup = jDao::get('acl~jaclgroup');
       $daoright = jDao::get('acl~jaclrights');
       $daousergroup = jDao::get('acl~jaclusergroup');
       // enlever tout les droits attach� au groupe
       $daoright->deleteByGroup($groupid);
       // enlever les utilisateurs du groupe
       $daousergroup->deleteByGroup($groupid);
       // suppression du groupe
       $daogroup->del($groupid);
    }

    // renvoi liste de groupe non personnel
    function getGroupList(){
       $daogroup = jDao::get('acl~jaclgroup');
       return $daogroup->findAllPublicGroup();
    }

}

?>