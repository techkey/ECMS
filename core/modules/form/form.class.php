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
   * @param array $data The build array.
   * @return string
   */
  public function build(array $data) {

    $this->form_info = get_module_session()->get_form_info();

    // The POST vars from the browser.
    $this->form_values = get_module_session()->get_form_data();

    // Check if the form is submitted.
    if ($this->form_values) {
      // The saved form.
      $saved_data = $this->form_info['data'];

      $this->validate($saved_data);
      if (!$this->form_errors) {
        // Call the validate handler if exists.
        $class = get_module(basename(str_replace('\\', '/', $this->form_info['caller_class'])));
        $method = $this->form_info['caller_method'] . '_validate';
        if (method_exists($class, $method)) {
          $class->$method($saved_data, $this->form_values, $this->form_errors);
        }
        if (!$this->form_errors) {
          // Call the submit handler if exists.
          $method = $this->form_info['caller_method'] . '_submit';
          if (method_exists($class, $method)) {
            // Call the submit handler.
            $class->$method($saved_data, $this->form_values);
            // Check if a destination is set.
            if (isset($_GET['destination'])) {
              $_SESSION['keepflashvars'] = TRUE;
              go_to($_GET['destination']);
            }
            // Check if we must redirect.
            if (isset($saved_data['#redirect'])) {
              go_to($saved_data['#redirect']);
            }
            // Just go to the same page.

          }
        }
      }
    }

    if ($this->form_errors) {
      foreach ($this->form_errors as $message) {
        set_message($message, 'error');
      }
    }

    $fields = $this->render_fields($data);

    // Get the caller.
    $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $caller = $caller[1];

    // Set the default form attributes if form attributes are not set.
    if (!isset($this->form_attributes['id'])) {
      $this->form_attributes['id'] = 'form-' . basename(str_replace('\\', '/', $caller['class'])) . '-' . $caller['function'];
    }
    $this->form_attributes += array(
      'action' => $_SERVER['REQUEST_URI'],
      'method' => 'post',
    );

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
   * Render the fields recursive.
   *
   * @param array $data The form array.
   * @return array
   */
  private function render_fields(array $data) {
    $fields = array();

    foreach ($data as $name => $field) {
      if ($name == '#attributes') {
        $this->form_attributes = $field;
        continue;
      }
      if ($name == '#description') {
        $this->form_description = $field;
        continue;
      }
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

    return $fields;
  }

  /**
   * todo: improve this
   *
   * @param array $array
   * @return array
   */
  private function array_flatten(array $array) {
    $a = array();

    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $value += array('#type' => 'markup');
        if ($value['#type'] == 'fieldset') {
          foreach ($value as $k => $v) {
            if (is_array($v)) {
              $a[$k] = $v;
            }
          }
        } else {
          $a[$key] = $value;
        }
      } else {
        $a[$key] = $value;
      }
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

        case 'fieldset':
        case 'markup':
        case 'submit':
        case 'value':
          break;

        case 'select':
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
   */
//  private function render_form($name, array $field) {
//    $this->form_attributes += $field;
//  }

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
    );

    $str = sprintf('<fieldset %s>', build_attribute_string($attributes));
    if (isset($field['#title'])) {
      $str .= '<legend>' . $field['#title'] . '</legend>';
    }
    if (isset($field['#description'])) {
      $str .= '<div class="form-fieldset-description">' . $field['#description'] . '</div>';
    }
    $str2 = $this->render_fields($field);
    $str .= implode('', $str2);

    return $str . '</fieldset>';
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

    $element[] = sprintf('<div id="form-element-%s" class="form-element">', $attributes['id']);
    $element[] = sprintf('<label %s>%s</label>', build_attribute_string($label_attribs), $field['#title']);
    $element[] = sprintf('<select %s>', build_attribute_string($attributes));
    foreach ($field['#options'] as $key => $value) {
      if ($key == $field['#default_value']) {
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
    if (isset($this->form_values[$name])) {
      $attributes['value'] = $this->form_values[$name];
    } else {
      $attributes['value'] = (isset($field['#default_value'])) ? $field['#default_value'] : '';
    }

    if (isset($this->form_errors[$name])) {
      $attributes['class'][] = 'form-error';
    }

    if ($type == 'password') {
      $attributes['value'] = '';
    }

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
  private function render_email($name, array $field) {
    return $this->render_textfield($name, $field, 'email');
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
}

