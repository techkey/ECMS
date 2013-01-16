# Easy CMS system

A small but fast and powerful CMS system.

The goal is to make it as small and fast as possible. At this moment it runs on
PDO SQLite3, so no database need to be setup on the server. It can run on
different theme engines (e.g. Smarty and Twig).

Required core modules are:

*   config
*   session
*   system

Recommended additional core modules are:

*   block
*   form
*   library
*   module
*   menu
*   router
*   user

ECMS exclusive theme engine and library files is 49 (small) files.

A small example of a module to add a menu link and, on click, outputs the text
'Hello':

    class test {

      /**
       * Hook menu().
       *
       * @return array
       */
      public function menu() {
        $menu['hello'] = array(
          'title'      => 'Hello',
          'controller' => 'test:hello',
        );

        return $menu;
      }

      /**
       * The controller.
       *
       * @return string
       */
      public function hello() {
        return 'Hello';
      }
    }

More examples and documentation follows soon.