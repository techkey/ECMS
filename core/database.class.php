<?php
/**
 * @file database.class.php
 */

use core\modules\config\config;

/**
 * The main database class.
 */
class database {

  private $pdo = NULL;
  private $name = '';
  private $mode = '';
  private $table = '';
  private $fields = array();
  private $orderby = array();
  private $conditions = array();

  private $limit = -1;
  private $offset = -1;

  /**
   * Initialize the class.
   */
  public function __construct() {
    if (!extension_loaded('PDO')) {
      exit('PDO Extension not found!');
    }
    $this->name = BASE_DIR . config::get_value('database.name');
    $dsn = 'sqlite:' . $this->name;
    try {
      $this->pdo = new PDO($dsn);
    } catch (PDOException $e) {
      echo 'Connection failed: ' . $e->getMessage() . '<br />';
      echo $this->name . '<br />';
//      exit;
    }

  }

  /**
   * Reset the class.
   */
  private function reset() {
    $this->mode = '';
    $this->table = '';
    $this->fields = array();
    $this->orderby = array();
    $this->conditions = array();
    $this->limit = -1;
    $this->offset = -1;
  }

  /**
   * @param string $table
   * @return database
   */
  public function select($table) {
    $this->reset();
    $this->mode = 'select';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return database
   */
  public function insert($table) {
    $this->reset();
    $this->mode = 'insert';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return database
   */
  public function update($table) {
    $this->reset();
    $this->mode = 'update';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return database
   */
  public function delete($table) {
    $this->reset();
    $this->mode = 'delete';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $field
   * @return database
   */
  public function field($field) {
    $this->fields = array($field);
    return $this;
  }

  /**
   * @param array $fields
   * @return database
   */
  public function fields(array $fields) {
    $this->fields += $fields;
    return $this;
  }

  /**
   * @param string $field
   * @param string $direction
   * @return database
   */
  public function orderby($field, $direction = 'ASC') {
    $this->orderby[$field] = $direction;
    return $this;
  }

  /**
   * @param string $field
   * @param mixed $value
   * @param string $operator
   * @return database
   */
  public function condition($field, $value, $operator = '=') {
    $this->conditions[] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );
    return $this;
  }

  /**
   * @param int $limit
   * @return database
   */
  public function pager($limit) {
    $this->limit = $limit;
    $this->offset = (isset($_GET['page'])) ? ((int)$_GET['page'] - 1) * $limit : 0;

    return $this;
  }

  /**
   * Build the query string and prepare.
   *
   * @return null|bool|int|PDOStatement Return type depends on the query.
   */
  public function execute() {
    $params = array();
    $conditions = array();

    switch ($this->mode) {

      case 'select':
        $fields = implode(',', $this->fields);
        $sql = "SELECT $fields FROM {$this->table}";
        break;

      case 'insert':
        $sql = "INSERT INTO {$this->table} ";
        $names = array();
        $values = array();
        foreach ($this->fields as $name => $value) {
          $params[':' . $name] = $value;
          $names[] = $name;
          $values[] = ':' . $name;
        }
        $names = implode(',', $names);
        $values = implode(',', $values);
        $sql .= "($names) VALUES ($values)";
        break;

      case 'update':
        $sql = "UPDATE {$this->table} SET ";
        $sets = array();
        foreach ($this->fields as $name => $value) {
          $params[':' . $name] = $value;
          $sets[] = $name . '=:' . $name;
        }
        $sets = implode(',', $sets);
        $sql .= $sets;
        break;

      case 'delete':
        $sql = "DELETE FROM {$this->table} ";
        break;

      default:
        return NULL;
    }

    /** Add conditions to the query string. */
    if ($this->conditions) {
      $sql .= ' WHERE ';
      foreach ($this->conditions as $condition) {
        $params[':' . $condition['field']] = $condition['value'];
        $conditions[] = $condition['field'] . $condition['operator'] . ':' . $condition['field'];
      }
      $sql .= implode(' AND ', $conditions);
    }

    /** Add orderby to the query string. */
    if ($this->orderby) {
      $sql .= ' ORDER BY ';
      $ordering = array();
      foreach ($this->orderby as $field => $direction) {
        $ordering[] = $field . ' ' . $direction;
      }
      $sql .= implode(', ', $ordering);
    }

    if ($this->mode == 'select') {
      if ($this->limit != -1) {
        $sql .= " LIMIT {$this->offset},{$this->limit}";
      }
    }

    return $this->prepare($sql, $params);
  }

  /**
   * @param string $query
   * @param array $args
   * @return bool|PDOStatement
   */
  public function query($query, array $args) {

    return $this->prepare($query, $args);
  }

  /**
   * @param string $sql
   * @param array  $params
   * @throws DBException
   * @return bool|int
   */
  private function prepare($sql, array $params) {
    /** @var PDOStatement $pdo */
    $pdo = $this->pdo->prepare($sql);
    if (!$pdo) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DBException(print_r($error_info, TRUE));
//      return FALSE;
    }
    $b = $pdo->execute($params);
    if ($b) {
      switch ($this->mode) {

        case 'update':
          return $pdo->rowCount();
          break;

        default:
          return $pdo;

      }
    } else {
      $error_info = $pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DBException(print_r($error_info, TRUE));
//      return FALSE;
    }
  }

  /**
   * @param string $table
   * @throws DBException
   * @return array|bool
   */
  public function table_info($table) {
    /** @var PDOStatement $st */
    $st = $this->pdo->query("PRAGMA table_info($table)");
    if ($st === FALSE) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DBException(print_r($error_info, TRUE));
//      return FALSE;
    }
    $r = $st->fetchAll(PDO::FETCH_ASSOC);
    return $r;
  }

  /**
   * @param string $sql
   * @throws DBException
   * @return bool|int
   */
  private function exec($sql) {
    $result = $this->pdo->exec($sql);
    if ($result === FALSE) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DBException(print_r($error_info, TRUE));
//      return FALSE;
    }
    return $result;
  }

  /**
   * @param string $type
   * @return string
   */
  private function field_type_to_sqlite($type) {
    switch ($type) {
      case 'serial' : $sqlite = 'INTEGER PRIMARY KEY AUTOINCREMENT'; break;
      case 'text'   : $sqlite = "TEXT"; break;
      case 'varchar': $sqlite = "TEXT"; break;
      case 'integer': $sqlite = 'INTEGER'; break;
      case 'tinyint': $sqlite = 'TINYINT'; break;
      case 'bool'   : $sqlite = 'BOOLEAN'; break;
      default: $sqlite = '';
    }
    return $sqlite;
  }

  /**
   * @param string $table_name
   * @param array $table_data
   * @return bool
   */
  private function create_table($table_name, array $table_data) {
    $sql = "CREATE TABLE $table_name (";
    $a = array();

    foreach ($table_data['fields'] as $field_name => $field_data) {
      $field_data += array(
        'length' => 255,
        'not null' => FALSE,
        'unique' => FALSE,
      );
      $sql2 = $field_name . ' ' . $this->field_type_to_sqlite($field_data['type']);
      if ($field_data['unique']) $sql2 .= ' UNIQUE';
      if ($field_data['not null']) $sql2 .= ' NOT NULL';
      if (isset($field_data['default'])) $sql2 .= " DEFAULT '{$field_data['default']}'";
      $a[] = $sql2;
    }

    $sql .= implode(',', $a);
    if (isset($table_data['primary key'])) {
      $pk = implode(',', $table_data['primary key']);
      $sql .= ",PRIMARY KEY ($pk)";
    }
    $sql .= ')';

    return $this->exec($sql);
  }

  /**
   * @param array $schema
   * @return bool
   */
  public function install_schema(array $schema) {
    foreach ($schema as $table_name => $table_data) {
      $b = $this->create_table($table_name, $table_data);
      if ($b === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * @param string $table
   * @param string $field_name
   * @param array $field_data
   */
  public function add_field($table, $field_name, array $field_data) {
    $sql = "ALTER TABLE $table ADD ";
    $field_data += array(
      'length' => 255,
      'not null' => FALSE,
//      'default' => NULL,
    );
    $sql2 = $field_name . ' ' . $this->field_type_to_sqlite($field_data['type']);
    if ($field_data['not null']) $sql2 .= ' NOT NULL';
    if (isset($field_data['default'])) $sql2 .= " DEFAULT '{$field_data['default']}'";
    $this->exec($sql . $sql2);
  }

  /**
   * @param string $table
   * @param string $field
   * @return bool
   */
  public function field_exists($table, $field) {
    $info = $this->table_info($table);
    if (!$info) return FALSE;
    foreach ($info as $i) {
      if ($i['name'] == $field) return TRUE;
    }
    return FALSE;
  }
}

/**
 *
 */
class DBException extends Exception {

  /**
   * @param string    $message
   * @param int       $code
   * @param Exception $previous
   */
  public function __construct($message = '', $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);

//    vardump($message);
//    vardump(debug_backtrace());
    echo '<pre>';
    debug_print_backtrace();
    exit;
  }

}


/**
 * @return database
 */
function get_dbase() {
  static $dbase = NULL;
  if ($dbase == NULL) {
    $dbase = new database();
  }
  return $dbase;
}

/**
 * @param string $query
 * @param array $args
 * @return PDOStatement|bool
 */
function db_query($query, array $args = array()) {
  return get_dbase()->query($query, $args);
}

/**
 * @param string $table
 * @return database
 */
function db_select($table) {
  return get_dbase()->select($table);
}

/**
 * @param string $table
 * @return database
 */
function db_insert($table) {
  return get_dbase()->insert($table);
}

/**
 * @param string $table
 * @return database
 */
function db_update($table) {
  return get_dbase()->update($table);
}

/**
 * @param string $table
 * @return database
 */
function db_delete($table) {
  return get_dbase()->delete($table);
}

/**
 * @param string $table
 * @return bool
 */
function db_table_exists($table) {
  return (BOOL)get_dbase()->table_info($table);
}

/**
 * @param array $schema
 * @return bool
 */
function db_install_schema(array $schema) {
  return get_dbase()->install_schema($schema);
}

/**
 * @param string $table
 * @param string $field_name
 * @param array $field_data
 */
function db_add_field($table, $field_name, array $field_data) {
  get_dbase()->add_field($table, $field_name, $field_data);
}

/**
 * @param string $table
 * @param string $field
 * @return bool
 */
function db_field_exists($table, $field) {
  return get_dbase()->field_exists($table, $field);
}
