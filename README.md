# Easy CMS system

A small but fast and powerful CMS system.

The goal is to make it as small and fast as possible.

Documentations is work in progress.

A minimum module to add a menu link:

    class test {

        /**
         * Hook menu().
         *
         * @return array
         */
        public function menu() {
          $menu['hello'] = array(
            'title'            => 'Hello',
            'controller'       => 'test:hello',
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
