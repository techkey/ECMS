<?php
/**
 * @file Database.class.php
 */

use core\modules\config\config;

/**
 * The main database class.
 */
class Database {

  /** @var PDO */
  private $pdo = NULL;
  /** @var string $type The database type (sqlite3 or mysql). */
  private $type = '';
  private $mode = '';
  private $table = '';
  private $fields = array();
  private $orderby = array();
  private $conditions = array();

  private $limit = -1;
  private $offset = -1;

  private $sqlite3_serial = FALSE;

  /**
   * Initialize the class.
   */
  public function __construct() {
    if (!extension_loaded('PDO')) {
      exit('PDO Extension not found!');
    }
  }

  /**
   * Connect using connection values from the config file or if set from the
   * $values array.
   *
   * @param array $values
   * @throws DatabaseException
   */
  public function connect(array $values = array()) {
    if ($values) {
      $this->type       = $values['type'];
      $sqlite3_filepath = $values['sqlite3_filepath'];
      $database_name    = $values['database_name'];
      $username         = $values['username'];
      $password         = $values['password'];
    } else {
      $this->type       = config::get_value('database.type');
      $sqlite3_filepath = config::get_value('database.sqlite3_filepath');
      $database_name    = config::get_value('database.database_name');
      $username         = config::get_value('database.username');
      $password         = config::get_value('database.password');
    }
    if (!$this->type) {
      return;
    }

    $this->type = strtolower($this->type);

    $uname = NULL;
    $pword = NULL;
    switch (strtolower($this->type)) {
      case 'sqlite3';
        if (!is_file($sqlite3_filepath)) {
          throw new DatabaseException('SQLite database file does not exists.');
        }
        $dsn = 'sqlite:' . $sqlite3_filepath;
        break;
      case 'mysql';
        $dsn = 'mysql:dbname=' . $database_name;
        $uname = $username;
        $pword = $password;
        break;
      default:
        exit(__LINE__ . ': Database type ' . $this->type . ' is not supported at this time.');
    }
    try {
      $this->pdo = new PDO($dsn, $uname, $pword);
    } catch (PDOException $e) {
//      echo 'Connection failed: ' . $e->getMessage() . '<br />';
//      echo $this->type . '<br />';
//      exit;
      throw new DatabaseException('Connection failed: ' . $e->getMessage());
    }
  }

  /**
   * @return bool
   */
  public function is_active() {
    return ($this->pdo) ? TRUE : FALSE;
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
   * @return Database
   */
  public function select($table) {
    $this->reset();
    $this->mode = 'select';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return Database
   */
  public function insert($table) {
    $this->reset();
    $this->mode = 'insert';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return Database
   */
  public function update($table) {
    $this->reset();
    $this->mode = 'update';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $table
   * @return Database
   */
  public function delete($table) {
    $this->reset();
    $this->mode = 'delete';
    $this->table = $table;
    return $this;
  }

  /**
   * @param string $field
   * @return Database
   */
  public function field($field) {
    $this->fields = array($field);
    return $this;
  }

  /**
   * @param array $fields
   * @return Database
   */
  public function fields(array $fields) {
    $this->fields += $fields;
    return $this;
  }

  /**
   * @param string $field
   * @param string $direction
   * @return Database
   */
  public function orderby($field, $direction = 'ASC') {
    $this->orderby[$field] = $direction;
    return $this;
  }

  /**
   * @param string $field
   * @param mixed $value
   * @param string $operator
   * @return Database
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
   * @return Database
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

    /** Add order by to the query string. */
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
   * @throws DatabaseException
   * @return bool|int
   */
  private function prepare($sql, array $params) {
    /** @var PDOStatement $pdo */
    $pdo = $this->pdo->prepare($sql);
    if (!$pdo) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DatabaseException(print_r($error_info, TRUE));
//      return FALSE;
    }
    $b = $pdo->execute($params);
    if ($b) {
      switch ($this->mode) {

        case 'insert':
          return $this->pdo->lastInsertId();
          break;

        case 'update':
          return $pdo->rowCount();
          break;

        default:
          return $pdo;

      }
    } else {
      $error_info = $pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DatabaseException(print_r($error_info, TRUE));
//      return FALSE;
    }
  }

  /**
   * @param string $table
   * @throws DatabaseException
   * @return array|bool
   */
  public function table_info($table) {

    switch ($this->type) {
      case 'sqlite3':
        /** @var PDOStatement $st */
        $st = $this->pdo->query("PRAGMA table_info($table)");
        break;

      case 'mysql':
        /** @var PDOStatement $st */
        $st = $this->pdo->query("DESCRIBE $table");
        break;

      default:
        exit(__LINE__ . ': Database type ' . $this->type . ' is not supported at this time.');

    }

    if ($st === FALSE) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DatabaseException(print_r($error_info, TRUE));
//      return FALSE;
    }
    $r = $st->fetchAll(PDO::FETCH_ASSOC);


    return $r;
  }

  /**
   * @param string $sql
   * @throws DatabaseException
   * @return bool|int
   */
  private function exec($sql) {
    $result = $this->pdo->exec($sql);
    if ($result === FALSE) {
      $error_info = $this->pdo->errorInfo();
//      wd_add('error', $error_info);
      throw new DatabaseException(print_r($error_info, TRUE));
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
      case 'serial' : $field_type = 'INTEGER PRIMARY KEY AUTOINCREMENT'; break;
      case 'text'   : $field_type = "TEXT"; break;
      case 'varchar': $field_type = "TEXT"; break;
      case 'integer': $field_type = 'INTEGER'; break;
      case 'tinyint': $field_type = 'TINYINT'; break;
      case 'bool'   : $field_type = 'BOOLEAN'; break;
      default: $field_type = '';
    }

    return $field_type;
  }

  /**
   * @param string $type
   * @return string
   */
  private function field_type_to_mysql($type) {
    switch ($type) {
      case 'serial' : $field_type = 'BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE'; break;
      case 'text'   : $field_type = "TEXT"; break;
      case 'varchar': $field_type = "VARCHAR"; break;
      case 'integer': $field_type = 'INTEGER'; break;
      case 'tinyint': $field_type = 'TINYINT'; break;
      case 'bool'   : $field_type = 'BOOLEAN'; break;
      default: $field_type = '';
    }

    return $field_type;
  }

  /**
   * @param array  $keys
   * @param string $field
   * @return bool|string
   */
  private function find_key(array $keys, $field) {
    foreach ($keys as $key => $fields) {
      if (in_array($field, $fields)) {
        return $key;
      }
    }
    return FALSE;
  }

  /**
   * @param string $field_name
   * @param array  $field_data
   * @return string
   */
  private function build_field_sql($field_name, array $field_data) {
    $field_data += array(
      'length' => 255,
      'not null' => FALSE,
      'unique' => FALSE,
    );
    switch ($this->type) {
      case 'sqlite3':
        $this->sqlite3_serial |= ($field_data['type'] == 'serial');
        $sql2 = $field_name . ' ' . $this->field_type_to_sqlite($field_data['type']);
        if ($field_data['not null']) $sql2 .= ' NOT NULL';
        if (isset($field_data['default'])) $sql2 .= " DEFAULT '{$field_data['default']}'";
        break;

      case 'mysql':
        $sql2 = $field_name . ' ' . $this->field_type_to_mysql($field_data['type']);
        if (in_array($field_data['type'], array('varchar', 'text'))) {
          $sql2 .= '(' . $field_data['length'] . ')';
        }
        if (isset($field_data['unsigned']) && $field_data['unsigned']) {
          $sql2 .= ' UNSIGNED';
        }
        if ($field_data['not null']) $sql2 .= ' NOT NULL';
        if (isset($field_data['default'])) $sql2 .= " DEFAULT '{$field_data['default']}'";
        break;

      default:
        exit(__LINE__ . ': Database type ' . $this->type . ' is not supported at this time.');
    }

    return $sql2;
  }

  /**
   * @param string $table_name
   * @param array $table_data
   * @return bool
   */
  private function create_table($table_name, array $table_data) {

    $sql = "CREATE TABLE $table_name (";
    $a = array();

    $this->sqlite3_serial = FALSE;
    foreach ($table_data['fields'] as $field_name => $field_data) {
      $a[] = $this->build_field_sql($field_name, $field_data);
    }

    $sql .= implode(',', $a);
    switch ($this->type) {
      case 'sqlite3':
        if (!$this->sqlite3_serial && isset($table_data['primary key'])) {
          $pk = implode(',', $table_data['primary key']);
          $sql .= ",PRIMARY KEY ($pk)";
        }
        if (isset($table_data['unique keys'])) {
          foreach ($table_data['unique keys'] as $key => $fields) {
            $uk = implode(',', $fields);
            $sql .= ",UNIQUE ($uk)";
          }
        }
        break;

      case 'mysql':
        if (isset($table_data['primary key'])) {
          $pk = implode(',', $table_data['primary key']);
          $sql .= ",PRIMARY KEY ($pk)";
        }
        if (isset($table_data['unique keys'])) {
          foreach ($table_data['unique keys'] as $key => $fields) {
            $uk = implode(',', $fields);
            $sql .= ",UNIQUE KEY $key ($uk)";
          }
        }
        if (isset($table_data['indexes'])) {
          foreach ($table_data['indexes'] as $key => $fields) {
            $uk = implode(',', $fields);
            $sql .= ",KEY $key ($uk)";
          }
        }
        break;

      default:
        exit(__LINE__ . ': Database type ' . $this->type . ' is not supported at this time.');
    }
    $sql .= ')';

    $b = $this->exec($sql);

    return ($b !== FALSE);
  }

  /**
   * @param string $table
   * @return bool
   */
  private function drop_table($table) {
    $r =  $this->pdo->query('DROP TABLE IF EXISTS ' . $table);
    return $r;
  }

  /**
   * Install a schema.
   *
   * For every table in the schema it first drops the table if exists, then it
   * create a new empty table. It also create the keys if given in the schema.
   *
   * @param array $schema The schema to be installed.
   * @return bool Returns TRUE on success and FALSE on failure.
   */
  public function install_schema(array $schema) {
    foreach ($schema as $table_name => $table_data) {
      $this->drop_table($table_name);
      $b = $this->create_table($table_name, $table_data);
      if ($b === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Uninstall a schema.
   *
   * Drop every table in the schema.
   *
   * @param array $schema The schema to be uninstalled.
   * @return bool
   */
  public function uninstall_schema(array $schema) {
    $failed = FALSE;
    foreach ($schema as $table_name => $table_data) {
      $failed |= !$this->drop_table($table_name);
    }
    return !$failed;
  }

  /**
   * @param string $table
   * @param string $field_name
   * @param array $field_data
   * @return bool|int
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

    return $this->exec($sql . $sql2);
  }

  /**
   * @param string $table
   * @param string $field
   * @return bool
   */
  public function field_exists($table, $field) {
    $return = FALSE;
    switch ($this->type) {
      case 'sqlite3':
        $info = $this->table_info($table);
        if (!$info) return FALSE;
        foreach ($info as $i) {
          if ($i['name'] == $field) {
            $return = TRUE;
          }
        }
        break;

      case 'mysql':
        $st = $this->query("SHOW COLUMNS FROM $table WHERE Field=:field", array(':field' => $field));
        $result = $st->fetchColumn();
        $return = (bool)$result;
        break;

    }

    return $return;
  }
}

/**
 *
 */
class DatabaseException extends Exception {

  /**
   * @param string    $message
   * @param int       $code
   * @param Exception $previous
   */
  public function __construct($message = '', $code = 0, Exception $previous = NULL) {
    parent::__construct($message, $code, $previous);

    echo $message;
    echo '<pre>';
    debug_print_backtrace();

    exit;
  }

}


/**
 * @return Database
 */
function get_dbase() {
  static $dbase = NULL;
  if ($dbase == NULL) {
    $dbase = new Database();
  }
  return $dbase;
}

/**
 * @return bool
 */
function db_is_active() {
  return get_dbase()->is_active();
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
 * @return Database
 */
function db_select($table) {
  return get_dbase()->select($table);
}

/**
 * @param string $table
 * @return Database
 */
function db_insert($table) {
  return get_dbase()->insert($table);
}

/**
 * @param string $table
 * @return Database
 */
function db_update($table) {
  return get_dbase()->update($table);
}

/**
 * @param string $table
 * @return Database
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
 * Install a schema.
 *
 * For every table in the schema it first drops the table if exists, then it
 * create a new empty table. It also create the keys if given in the schema.
 *
 * @param array $schema The schema to be installed.
 * @return bool Returns TRUE on success and FALSE on failure.
 */
function db_install_schema(array $schema) {
  return get_dbase()->install_schema($schema);
}

/**
 * Uninstall a schema.
 *
 * Drop every table in the schema.
 *
 * @param array $schema The schema to be uninstalled.
 * @return bool
 */
function db_uninstall_schema(array $schema) {
  return get_dbase()->uninstall_schema($schema);
}

/**
 * @param string $table
 * @param string $field_name
 * @param array $field_data
 * @return bool
 */
function db_add_field($table, $field_name, array $field_data) {
  return (bool)get_dbase()->add_field($table, $field_name, $field_data);
}

/**
 * @param string $table
 * @param string $field
 * @return bool
 */
function db_field_exists($table, $field) {
  return get_dbase()->field_exists($table, $field);
}
