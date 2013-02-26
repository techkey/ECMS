<?php
// form.class.php

namespace core\modules\form;

/**
 * The class to handle forms.
 */
class form {

  /**
   * @var array
   */
  private $form_attributes = array();

  private $form_description = '';

  /**
   * <ul>
   *  <li><id - The id in the format form_'class'_'method'.</li>
   *  <li>key - The generated UUID.</li>
   *  <li>method - get or post.</li>
   *  <li>caller_class - The caller class.</li>
   *  <li>caller_method - The caller method.</li>
   * </ul>
   *
   * @var array
   */
  private $form_info = array();
  private $form_values = array();
  private $form_errors = array();

  /**
   * @param string $form_name The name of the form method.
   * @param ...               [optional] Variable number of arguments.
   * @return string
   */
  public function build($form_name, $arg = NULL) {
    $args = func_get_args();
    array_shift($args);

    $this->form_info = get_module_session()->get_form_info();

    // The POST vars from the browser.
    $this->form_values = get_module_session()->get_form_data();

    $saved_data = array();

    // Check if the form is submitted.
    if ($this->form_values) {
      // The saved form.
      $saved_data = $this->form_info['data'];

      // Merge values of type '#value'.
      foreach ($saved_data as $key => $fields) {
        if ($key[0] == '#') {
          continue;
        }
        if (!isset($this->form_values[$key]) && ($fields['#type'] == 'value')) {
          $this->form_values[$key] = $fields['#value'];
        }
      }

      $this->validate($saved_data);
      if (!$this->form_errors) {

        // Create temporary array with referenced arguments.
        $args_tmp = array();
        $args_tmp[] = &$saved_data;
        $args_tmp[] = &$this->form_values;
        $args_tmp[] = &$this->form_errors;
        $args_tmp = array_merge($args_tmp, $args);

        // Check for additional validate handlers.
        if (isset($saved_data['#validate'])) {
          foreach ($saved_data['#validate'] as $handler) {
            list($handler_class_name, $handler_method_name) = explode('::', $handler);
            $handler_class = get_module($handler_class_name);
            if ($handler_class) {
              if (method_exists($handler_class, $handler_method_name)) {
                call_user_func_array(array($handler_class, $handler_method_name), $args_tmp);
              }
            }
          }
        }

        // Call the validate handler if exists.
        $class = get_module(basename(str_replace('\\', '/', $this->form_info['caller_class'])));
        $method = $this->form_info['caller_method'] . '_validate';
        if (method_exists($class, $method)) {
          call_user_func_array(array($class, $method), $args_tmp);
        }

        if (!$this->form_errors) {

          // Create temporary array with referenced arguments.
          $args_tmp = array();
          $args_tmp[] = &$saved_data;
          $args_tmp[] = &$this->form_values;
          $args_tmp = array_merge($args_tmp, $args);

          // Check for additional submit handlers.
          if (isset($saved_data['#submit'])) {
            foreach ($saved_data['#submit'] as $handler) {
              list($handler_class_name, $handler_method_name) = explode('::', $handler);
              $handler_class = get_module($handler_class_name);
              if ($handler_class) {
                if (method_exists($handler_class, $handler_method_name)) {
                  call_user_func_array(array($handler_class, $handler_method_name), $args_tmp);
                }
              }
            }
          }

          // Call the submit handler if exists.
          $method = $this->form_info['caller_method'] . '_submit';
          if (method_exists($class, $method)) {

            // Call the submit handler.
            $return = call_user_func_array(array($class, $method), $args_tmp);

            // Check if we have a message for the user.
            if ($return) {
              return $return;
            }

            // Check if a destination is set.
            if (isset($_GET['destination'])) {
              $_SESSION['keepflashvars'] = TRUE;
              go_to($_GET['destination']);
            }

            // Check if we must redirect.
            if (isset($saved_data['#redirect'])) {
              $_SESSION['keepflashvars'] = TRUE;
              go_to($saved_data['#redirect']);
            }

            // Just stay on the same page.

          }
        }
      }
    }

    if ($this->form_errors) {
      foreach ($this->form_errors as $message) {
        set_message($message, 'error');
      }
    }

    // Get the caller.
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $caller = $trace[1];
    if (basename(str_replace('\\', '/', $caller['class'])) == 'form') {
      $caller = $trace[2];
    }
    $caller['function'] = $form_name;

    $caller['args'] = $args;

    // Get the form data.
    $class = get_module(basename(str_replace('\\', '/', $caller['class'])));
//    array_unshift($args, $saved_data, $this->form_values, $this->form_errors);
//    $data = call_user_func_array(array($class, $form_name), $args);
    $args_tmp = array();
    $args_tmp[] = &$saved_data;
    $args_tmp[] = &$this->form_values;
    $args_tmp = array_merge($args_tmp, $args);
    $data = call_user_func_array(array($class, $form_name), $args_tmp);

    // Check for form attributes and description.
    if (isset($data['#attributes'])) {
      $this->form_attributes = $data['#attributes'];
    }
    if (isset($data['#description'])) {
      $this->form_description = $data['#description'];
    }

    // Set the default form attributes if form attributes are not set.
    if (!isset($this->form_attributes['id'])) {
      $this->form_attributes['id'] = 'form-' . basename(str_replace('\\', '/', $caller['class'])) . '-' . $caller['function'];
    }
    $this->form_attributes += array(
      'action' => $_SERVER['REQUEST_URI'],
      'method' => 'post',
    );

    // Hook form_alter().
    $enabled_module_instances = get_module_module()->get_enabled_module_instances();
    $method = "form_alter";

    foreach ($enabled_module_instances as $name => $class) {
      if (method_exists($class, $method)) {
        $class->$method($data, $this->form_values, $this->form_attributes['id']);
      }
    }

    // Hook form_FORM_ID_alter().
    $method = "form_{$form_name}_alter";

    foreach ($enabled_module_instances as $name => $class) {
      if (method_exists($class, $method)) {
        $class->$method($data, $this->form_values, $this->form_attributes['id']);
      }
    }

    // Filter possible duplicates of validate handlers.
    if (isset($data['#validate'])) {
      $data['#validate'] = array_unique($data['#validate']);
    }

    // Render the form fields.
    $fields = $this->render_fields($data);

    $this->form_info = $this->form_attributes;

    $this->form_info += array(
      'key' => create_uuid(),
      'caller_class' => $caller['class'],
      'caller_method' => $caller['function'],
      'data' => $data,
    );

    // Register the form.
    get_module_session()->register_form($this->form_info);

    // Finish the form
    if ($this->form_description != '') {
      array_unshift($fields, '<div class="form-description">' . $this->form_description . '</div>');
    }
    array_unshift($fields, sprintf('<input type="hidden" name="form_id" value="%s">', $this->form_attributes['id']));
    array_unshift($fields, sprintf('<input type="hidden" name="form_key" value="%s">', $this->form_info['key']));
    array_unshift($fields, sprintf('<form %s>', build_attribute_string($this->form_attributes)));
    array_push($fields, '</form>');

    // Return the rendered form as a string.
    return implode("\n", $fields);
  }

  /**
   * Build a confirm form.
   *
   * @param array $data Associative array with the following keys:
   * <pre>
   *  string title   - The page title
   *  string message - The message
   *  string button  - The button text
   *  string cancel  - The cancel path
   *  mixed  extra   - Extra data to be put in form_values
   * </pre>
   * @return array|string
   */
/*
  public function confirm(array $data) {
    $data += array(
      'title' => '',
      'message' => '',
      'extra' => NULL,
    );

    if ($data['extra']) {
      $form['extra'] = array(
        '#type' => 'value',
        '#value' => $data['extra'],
      );
    }
    if ($data['message']) {
      $form['message'] = array(
        '#value' => '<p>' . $data['message'] . '</p>',
      );
    }
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $data['button'],
      '#suffix' => '&nbsp;&nbsp;' . l('Cancel', $data['cancel']),
    );

    $str = $this->build($form);

    if ($data['title']) {
      $return = array(
        'page_title' => $data['title'],
        'content' => $str,
      );
    } else {
      $return = $str;
    }

    return $return;
  }
*/

  /**
   * Render the fields recursive.
   *
   * @param array $data The form array.
   * @return array
   */
  private function render_fields(array &$data) {
    $fields = array();

    foreach ($data as $name => &$field) {
      if ($name[0] == '#') continue;

      $str = '';
      $field += array(
        '#type' => 'markup',
        '#prefix' => '',
        '#suffix' => '',
        '#default_value' => NULL,
      );
      // Render the fields.
      switch ($field['#type']) {

        case 'checkbox':
          $str = $this->render_checkbox($name, $field);
          break;
        case 'checkboxes':
          $str = $this->render_checkboxes($name, $field);
          break;
        case 'email':
          $str = $this->render_email($name, $field);
          break;
        case 'fieldset':
          $str = $this->render_fieldset($name, $field);
          break;
        case 'markup':
          $str = $field['#value'];
          break;
        case 'number':
          $str = $this->render_number($name, $field);
          break;
        case 'password':
          $str = $this->render_password($name, $field);
          break;
        case 'radio':
          $str = $this->render_radio($name, $field);
          break;
        case 'radios':
          $str = $this->render_radios($name, $field);
          break;
        case 'submit':
          $str = $this->render_submit($name, $field);
          break;
        case 'select':
          $str = $this->render_select($name, $field);
          break;
        case 'textarea':
          $str = $this->render_textarea($name, $field);
          break;
        case 'textfield':
          $str = $this->render_textfield($name, $field);
          break;
        case 'value':
          continue;
          break;

        default:
          set_message(__LINE__ . ": Unknown fieldtype <em>{$field['#type']}</em>.", 'warning');
          continue;
          break;
      }
      // Add prefix and suffix.
      $fields[] = $field['#prefix'] . $str . $field['#suffix'];
    }
    unset($field);

    return $fields;
  }

  /**
   * Flattens a array.
   *
   * @param array $array
   * @return array
   */
  private function array_flatten(array $array) {
    $a = array();

    $more = FALSE;
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $value += array('#type' => 'markup');
        if ($value['#type'] == 'fieldset') {
          foreach ($value as $k => $v) {
            if (is_array($v)) {
              $a[$k] = $v;
              $v += array('#type' => 'markup');
              if ($v['#type'] == 'fieldset') {
                $more = TRUE;
              }
            }
          }
        } else {
          $a[$key] = $value;
        }
      } else {
        $a[$key] = $value;
      }
    }

    if ($more) {
      $a = $this->array_flatten($a);
    }

    return $a;
  }

  /**
   * @param array $data The form array.
   */
  private function validate(array $data) {
    $data = $this->array_flatten($data);

    // Fix checkbox values.


    foreach ($data as $name => $field) {
      if ($name[0] == '#') continue;

      $field += array('#type' => 'markup');
      switch ($field['#type']) {

        case 'checkbox':
           if (isset($this->form_values[$name])) {
             $this->form_values[$name] = TRUE;
           } else {
             $this->form_values[$name] = FALSE;
           }
          break;

        case 'checkboxes':
        case 'fieldset':
        case 'markup':
        case 'radio':
        case 'radios':
        case 'submit':
        case 'value':
          break;

        /** @noinspection PhpMissingBreakStatementInspection */
        case 'select':
          if (isset($field['#multiple']) && ($field['#multiple'] == 'multiple')) {
            if (!isset($this->form_values[$name])) {
              $this->form_values[$name] = array();
            }
            break;
          }
        case 'textarea':
          $field += array(
            '#required' => FALSE,
          );
          if ($field['#required'] && ($this->form_values[$name] == '')) {
            $this->form_errors[$name] = sprintf('Field <em>%s</em> is required.', $name);
          }
          break;

        case 'number':
        case 'email':
        case 'password':
        case 'textfield':
          $field += array(
            '#required' => FALSE,
            '#maxlength' => 128,
          );

          if ($field['#required'] && ($this->form_values[$name] == '')) {
            $this->form_errors[$name] = sprintf('Field <em>%s</em> is required.', $name);
          }
          elseif ($this->form_values[$name] != '') {
            if (strlen($this->form_values[$name]) > $field['#maxlength']) {
              $this->form_errors[$name] = sprintf('Field <em>%s</em> cannot exceed length of %d.', $name, $field['#maxlength']);
            }
            elseif (isset($field['#minlength']) && (strlen($this->form_values[$name]) < $field['#minlength'])) {
              $this->form_errors[$name] = sprintf('Field <em>%s</em> cannot be less then %d characters.', $name, $field['#minlength']);
            }
          }
          break;

        default:
          set_message(__LINE__ . ": Unknown fieldtype <em>{$field['#type']}</em>.", 'error');
//          throw new \Exception("Unknown fieldtype '{$field['#type']}'.");

      }
    }
  }


  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_checkbox($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
    );

    $label_attribs['for'] = $attributes['id'];

    if ($field['#default_value']) {
      $attributes['checked'] = 'checked';
    }

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    if (isset($field['#title_display']) && ($field['#title_display'] == 'before')) {
      $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
      $element[] = sprintf('<input type="checkbox" %s>', build_attribute_string($attributes));
    } else {
      $element[] = sprintf('<input type="checkbox" %s>', build_attribute_string($attributes));
      $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    }
    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }
    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_checkboxes($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
    );

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);

    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }

    $group_id = $attributes['id'];
//    $group_name = $attributes['name'];

    foreach ($field['#options'] as $key => $title) {
      $attributes['id'] = $group_id . '-' . $title;
      $attributes['name'] = $group_id . '-' . $key;
      $attributes['value'] = $key;
//      if ($field['#default_value'] == $key) {
//        $attributes['checked'] = 'checked';
//      } else {
//        unset($attributes['checked']);
//      };

      $label_attribs['for'] = $attributes['id'];

      if (isset($field['#field_prefix'])) {
        $element[] = $field['#field_prefix'];
      }

      if (isset($field['#title_display']) && ($field['#title_display'] == 'before')) {
        $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $title);
        $element[] = sprintf('<input type="checkbox" %s>', build_attribute_string($attributes));
      } else {
        $element[] = sprintf('<input type="checkbox" %s>', build_attribute_string($attributes));
        $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $title);
      }

      if (isset($field['#field_suffix'])) {
        $element[] = $field['#field_suffix'];
      }
    }

    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_email($name, array $field) {
    return $this->render_textfield($name, $field, 'email');
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_fieldset($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
      'class' => array(),
    );

    if (isset($field['#collapsible']) && $field['#collapsible']) {
      $attributes['class'][] = 'collapsible';
    }
    if (isset($field['#collapsed']) && $field['#collapsed']) {
      $attributes['class'][] = 'collapsed';
    }

    $str = sprintf('<fieldset %s>', build_attribute_string($attributes));
    if (isset($field['#title'])) {
      $str .= '<legend><span class="fieldset-legend">' . $field['#title'] . '</span></legend>';
    }
    $str .= '<div class="fieldset-wrapper">';
    if (isset($field['#description'])) {
      $str .= '<div class="form-fieldset-description">' . $field['#description'] . '</div>';
    }

    $str2 = $this->render_fields($field);

    $str .= implode('', $str2);
    $str .= '</div>';

    return $str . '</fieldset>';
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_number($name, array $field) {
    return $this->render_textfield($name, $field, 'number');
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_password($name, array $field) {
    return $this->render_textfield($name, $field, 'password');
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_radio($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
    );

    $label_attribs['for'] = $attributes['id'];

    if ($field['#default_value']) {
      $attributes['checked'] = 'checked';
    }

    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    if (isset($field['#title_display']) && ($field['#title_display'] == 'before')) {
      $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
      $element[] = sprintf('<input type="radio" %s>', build_attribute_string($attributes));
    } else {
      $element[] = sprintf('<input type="radio" %s>', build_attribute_string($attributes));
      $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    }
    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_radios($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
    );

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);

    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }

    $group_id = $attributes['id'];
//    $group_name = $attributes['name'];

    if ($field['#title']) {
      $element[] = "<label>{$field['#title']}</label>";
    }

    foreach ($field['#options'] as $key => $title) {
      $attributes['id'] = $group_id . '-' . $title;
      $attributes['value'] = $key;
      if ($field['#default_value'] == $key) {
        $attributes['checked'] = 'checked';
      } else {
        unset($attributes['checked']);
      };

      $label_attribs['for'] = $attributes['id'];

      if (isset($field['#field_prefix'])) {
        $element[] = $field['#field_prefix'];
      }

      if (isset($field['#title_display']) && ($field['#title_display'] == 'before')) {
        $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $title);
        $element[] = sprintf('<input type="radio" %s>', build_attribute_string($attributes));
      } else {
        $element[] = sprintf('<input type="radio" %s>', build_attribute_string($attributes));
        $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $title);
      }

      if (isset($field['#field_suffix'])) {
        $element[] = $field['#field_suffix'];
      }
    }

    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_select($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
    );

    if (isset($this->form_errors[$name])) {
      $attributes['class'][] = 'form-error';
    }

    $label_attribs['for'] = $attributes['id'];

    if (isset($field['#required']) && $field['#required']) {
      $attributes += array('required' => 'required');
      $label_attribs['class'] = 'required';
      if (!isset($field['#options'][''])) {
        $a[''] = '--please select--';
        $field['#options'] = $a + $field['#options'];
      }
    }

    if (isset($field['#multiple']) && ($field['#multiple'] == 'multiple')) {
      $attributes += array('multiple' => 'multiple');
      $attributes['name'] .= '[]';
    }

    if (isset($field['#size'])) {
      $attributes += array('size' => $field['#size']);
    }

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    $element[] = sprintf('<select %s>', build_attribute_string($attributes));
    $form_value = isset($this->form_values[$name]) ? $this->form_values[$name] : $field['#default_value'];
    if (!is_array($form_value)) {
      $form_value = array($form_value);
    }
    foreach ($field['#options'] as $key => $value) {
      if (in_array($key, $form_value)) {
        $element[] = sprintf('<option selected="selected" value="%s">%s</option>', $key, $value);
      } else {
        $element[] = sprintf('<option value="%s">%s</option>', $key, $value);
      }
    }
    $element[] = '</select>';
    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }
    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_submit($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'type' => 'submit',
      'id' => $name,
      'name' => $name,
    );
    if (isset($field['#value'])) {
      $attributes += array('value' => $field['#value']);
    }
    return sprintf('<input %s>', build_attribute_string($attributes));
  }

  /**
   * @param string $name
   * @param array $field
   * @return string
   */
  private function render_textarea($name, array $field) {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'id' => $name,
      'name' => $name,
      'cols' => (isset($field['#cols'])) ? $field['#cols'] : 60,
      'rows' => (isset($field['#rows'])) ? $field['#rows'] : 5,
    );
    if (isset($field['#placeholder'])) {
      $attributes['placeholder'] = $field['#placeholder'];
    }

    if (isset($this->form_values[$name])) {
      $value = $this->form_values[$name];
    } else {
      $value = (isset($field['#default_value'])) ? $field['#default_value'] : '';
    }

    if (isset($this->form_errors[$name])) {
      $attributes['class'][] = 'form-error';
    }

    $label_attribs['for'] = $attributes['id'];

    if (isset($field['#required']) && $field['#required']) {
      $attributes += array('required' => 'required');
      $label_attribs['class'] = 'required';
    }

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    $element[] = sprintf('<textarea %s>%s</textarea>', build_attribute_string($attributes), $value);
    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }
    $element[] = '</div>';

    return implode("\n", $element);
  }

  /**
   * @param string $name
   * @param array $field
   * @param string $type
   * @return string
   */
  private function render_textfield($name, array $field, $type = 'text') {
    $attributes = (isset($field['#attributes'])) ? $field['#attributes'] : array();
    $attributes += array(
      'type' => $type,
      'id' => $name,
      'name' => $name,
      'size' => (isset($field['#size'])) ? $field['#size'] : 64,
      'maxlength' => (isset($field['#maxlength'])) ? $field['#maxlength'] : 128,
    );
    if (isset($field['#placeholder']) && in_array($type, array('password', 'text'))) {
      $attributes['placeholder'] = $field['#placeholder'];
    }

    if (isset($this->form_values[$name])) {
      $attributes['value'] = $this->form_values[$name];
    } else {
      $attributes['value'] = (isset($field['#default_value'])) ? $field['#default_value'] : '';
    }

    if (isset($this->form_errors[$name])) {
      $attributes['class'][] = 'form-error';
    }

//    if ($type == 'password') {
//      $attributes['value'] = '';
//    }

    $label_attribs['for'] = $attributes['id'];

    if (isset($field['#required']) && $field['#required']) {
      $attributes += array('required' => 'required');
      $label_attribs['class'] = 'required';
    }

    if (isset($field['#default_value'])) {
      $attributes += array('value' => $field['#default_value']);
    }

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    $element[] = sprintf('<input %s>', build_attribute_string($attributes));
    if (isset($field['#description'])) {
      $element[] = sprintf('<span>%s</span>', $field['#description']);
    }
    $element[] = '</div>';

    return implode("\n", $element);
  }
}

