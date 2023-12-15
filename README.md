# CodeIgniter 4 Blade

## Installation

Install easily via Composer to take advantage of CodeIgniter 4's autoloading capabilities
and always be up-to-date:

```console
composer require aminos/codeigniter-blade
```

Or, install manually by downloading the source files and adding the directory to
`app/Config/Autoload.php`.

#### Usage (Helper function)
```php
<?php

class Home extends BaseController {
    public function index() {
        /** load blade helper function */
        helper('blade');
        
        /*
         * create {view_name}.blade.php inside Views folder
         */
        $data = ['key' => 'value'];
        
        return blade('view_name', $data);
    }
}
```

#### Usage (service)
```php
<?php

class Home extends BaseController {
    public function index() {
        /** load blade service */
        $blade = \Config\Services::blade(); // or service('blade')
        
        /*
         * create {view_name}.blade.php inside Views folder
         */
        $data = ['key' => 'value'];
        
        return $blade->render('view_name', $data);
    }
}
```

### Blade Documentation
[Larvel Blade Templates Docs](https://laravel.com/docs/10.x/blade)
