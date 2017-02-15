<?php

abstract class PhabricatorSearchHost
  extends Phobject {

  const KEY_REFS = 'cluster.search.refs';
  const KEY_HEALTH = 'cluster.search.health';

  protected $healthRecord;
  protected $roles = array();

  protected $disabled;
  protected $host;
  protected $port;
  protected $hostRefs = array();

  const STATUS_OKAY = 'okay';
  const STATUS_FAIL = 'fail';


  public function setDisabled($disabled) {
    $this->disabled = $disabled;
    return $this;
  }

  public function getDisabled() {
    return $this->disabled;
  }

  public function isWritable() {
    return $this->hasRole('write');
  }

  public function isReadable() {
    return $this->hasRole('read');
  }

  public function hasRole($role) {
    return isset($this->roles[$role]) && $this->roles[$role] === true;
  }

  public function setRoles(array $roles) {
    foreach ($roles as $role => $val) {
      $this->roles[$role] = $val;
    }
    return $this;
  }

  public function getRoles() {
    return $this->roles;
  }

  public function setPort($value) {
    $this->port = $value;
    return $this;
  }

  public function getPort() {
    return $this->port;
  }

  public function setHost($value) {
    $this->host = $value;
    return $this;
  }

  public function getHost() {
    return $this->host;
  }


  public function getHealthRecordCacheKey() {
    $host = $this->getHost();
    $port = $this->getPort();
    $key = self::KEY_HEALTH;

    return "{$key}({$host}, {$port})";
  }

/**
 * @return PhabricatorClusterServiceHealthRecord
 */
  public function getHealthRecord() {
    if (!$this->healthRecord) {
      $this->healthRecord = new PhabricatorClusterServiceHealthRecord(
        $this->getHealthRecordCacheKey());
    }
    return $this->healthRecord;
  }

  public function didHealthCheck($reachable) {
    $record = $this->getHealthRecord();
    $should_check = $record->getShouldCheck();

    if ($should_check) {
      $record->didHealthCheck($reachable);
    }
  }

  /**
   * @return string[] Get a list of fields to show in the status overview UI
   */
  abstract public function getStatusViewColumns();

  /**
   * @return PhabricatorFulltextStorageEngine
   */
  abstract public function getEngine();

  abstract public function getConnectionStatus();

  public static function reindexAbstractDocument(
    PhabricatorSearchAbstractDocument $doc) {

    $services = self::getAllServices();
    $indexed = 0;
    foreach (self::getWritableHostForEachService() as $host) {
      $host->getEngine()->reindexAbstractDocument($doc);
      $indexed++;
    }
    if ($indexed == 0) {
      throw new PhabricatorClusterNoHostForRoleException('write');
    }
  }

  public static function executeSearch(PhabricatorSavedQuery $query) {
    $services = self::getAllServices();
    foreach ($services as $service) {
      $hosts = $service->getAllHostsForRole('read');
      // try all hosts until one succeeds
      foreach ($hosts as $host) {
        $last_exception = null;
        try {
          $res = $host->getEngine()->executeSearch($query);
          // return immediately if we get results without an exception
          return $res;
        } catch (Exception $ex) {
          // try each server in turn, only throw if none succeed
          $last_exception = $ex;
        }
      }
    }
    if ($last_exception) {
      throw $last_exception;
    }
    return $res;
  }

}
