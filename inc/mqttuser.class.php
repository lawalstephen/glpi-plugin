<?php
/**
 * LICENSE
 *
 * Copyright © 2016-2017 Teclib'
 * Copyright © 2010-2017 by the FusionInventory Development Team.
 *
 * This file is part of Flyve MDM Plugin for GLPI.
 *
 * Flyve MDM Plugin for GLPI is a subproject of Flyve MDM. Flyve MDM is a mobile
 * device management software.
 *
 * Flyve MDM Plugin for GLPI is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * Flyve MDM Plugin for GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 * You should have received a copy of the GNU Affero General Public License
 * along with Flyve MDM Plugin for GLPI. If not, see http://www.gnu.org/licenses/.
 * ------------------------------------------------------------------------------
 * @author    Thierry Bugier Pineau
 * @copyright Copyright © 2017 Teclib
 * @license   AGPLv3+ http://www.gnu.org/licenses/agpl.txt
 * @link      https://github.com/flyve-mdm/glpi-plugin
 * @link      https://flyve-mdm.com/
 * ------------------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * @since 0.1.0
 */
class PluginFlyvemdmMqttuser extends CommonDBTM {

   /**
    * @see CommonDBTM::prepareInputForAdd()
    */
   public function prepareInputForAdd($input) {
      if (isset($input['password'])) {
         $input['password'] = $this->hashPassword($input['password']);
      }

      if (!isset($input['_reset_acl'])) {
         $input['_reset_acl'] = false;
      }

      return $input;
   }

   /**
    * @see CommonDBTM::prepareInputForUpdate()
    */
   public function prepareInputForUpdate($input) {
      if (isset($input['password'])) {
         $input['password'] = $this->hashPassword($input['password']);
      }

      if (!isset($input['_reset_acl'])) {
         $input['_reset_acl'] = false;
      }

      return $input;
   }

   /**
    * @see CommonDBTM::post_addItem()
    */
   public function post_addItem() {
      if ($this->input['_reset_acl'] === true) {
         $mqttAcl = new PluginFlyvemdmMqttacl();
         $mqttAcl->removeAllForUser($this);
      }
      if (isset($this->input['_acl']) && is_array($this->input['_acl'])) {
         foreach ($this->input['_acl'] as $acl) {
            if (isset($acl['topic']) && isset($acl['access_level'])) {
               $mqttAcl = new PluginFlyvemdmMqttacl();
               $mqttAcl->add([
                     'plugin_flyvemdm_mqttusers_id'   => $this->fields['id'],
                     'topic'                          => $acl['topic'],
                     'access_level'                   => $acl['access_level'],
               ]);
            }
         }
      }
   }

   /**
    * @see CommonDBTM::post_updateItem()
    */
   public function post_updateItem($history=1) {
      if ($this->input['_reset_acl'] === true) {
         $mqttAcl = new PluginFlyvemdmMqttacl();
         $mqttAcl->removeAllForUser($this);
      }
      if (isset($this->input['_acl']) && is_array($this->input['_acl'])) {
         foreach ($this->input['_acl'] as $acl) {
            if (isset($acl['topic']) && isset($acl['access_level'])) {
               $mqttAcl = new PluginFlyvemdmMqttacl();
               $mqttAcl->add([
                     'plugin_flyvemdm_mqttusers_id'   => $this->fields['id'],
                     'topic'                          => $acl['topic'],
                     'access_level'                   => $acl['access_level'],
               ]);
            }
         }
      }
   }

   /**
    * Hash a password
    * @param string $clearPassword
    * @return string PBKDF2 hashed password
    */
   protected function hashPassword($clearPassword) {
      // These parameters may be added to the function as a future improvement
      $algorithm = 'sha256';
      $saltSize = 12;
      $keyLength = 24;
      $salt = base64_encode(openssl_random_pseudo_bytes($saltSize));
      $iterations = 901;
      $rawOutput = true;

      if ($rawOutput) {
         $keyLength *= 2;
      }

      $hashed = hash_pbkdf2('sha256', $clearPassword, $salt, $iterations, $keyLength, $rawOutput);

      return 'PBKDF2$' . $algorithm . '$' . $iterations . '$' . $salt . '$' . base64_encode($hashed);
   }

   /**
    * Generate a random password havind a determined set pf chars
    * http://stackoverflow.com/a/31284266
    * @param integer $length password length to generate
    * @param string $keyspace characters available to build the password
    * @return string
    * @throws Exception
    */
   public static function getRandomPassword($length = 0, $keyspace= '') {
      if ($length == 0) {
         $length = '32';
      }

      if ($keyspace == '') {
         $keyspace = '0123456789';
         $keyspace = $keyspace . 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
         //$keyspace = $keyspace . '&#{[|]}@^_*%<>.,;:!';
      }

      $password = '';
      $max = mb_strlen($keyspace, '8bit') - 1;
      if ($max < 1) {
         throw new Exception('$keyspace must be at least two characters long');
      }

      for ($i = 0; $i < $length; $i++) {
         // random_int needs PHP 7, not yet widely used
         if (function_exists('random_int')) {
            $password .= $keyspace[random_int(0, $max)];
         } else {
            $password .= $keyspace[mt_rand(0, $max)];
         }
      }

      return $password;
   }

   /**
    * @see CommonDBTM::post_purgeItem()
    */
   public function post_purgeItem() {
      $mqttAcl = new PluginFlyvemdmMqttacl();
      $mqttAcl->deleteByCriteria([
            'plugin_flyvemdm_mqttusers_id'   => $this->getID()
      ]);
   }

   /**
    * Retrieve a mqtt user by name
    * @param string $user
    * @return bool
    */
   public function getByUser($user) {
      global $DB;

      $user = $DB->escape($user);
      return $this->getFromDBByQuery("WHERE `user` = '$user'");
   }

   /**
    * Returns an array of PluginFlyvemdmMqttACL for the user
    *
    * @return PluginFlyvemdmMqttacl[]
    */
   public function getACLs() {
      if ($this->isNewItem()) {
         return [];
      }

      $aclList = [];
      $mqttAcl = new PluginFlyvemdmMqttacl();
      $userId = $this->fields['id'];
      $rows = $mqttAcl->find("`plugin_flyvemdm_mqttusers_id` = '$userId'");
      foreach ($rows as $row) {
         $mqttAcl = new PluginFlyvemdmMqttacl();
         $mqttAcl->getFromDB($row['id']);
         if (!$mqttAcl->isNewItem()) {
            $aclList[] = $mqttAcl;
         }
      }

      return $aclList;
   }
}
