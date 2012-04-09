# Sanction Plugin for CakePHP

Centralize all of those permissions in a single file using the Sanction plugin.

## Background

Sanction is the culmination of lots of auth work on CakePackages. I was using the [CakePHP Authsome](https://github.com/felixge/cakephp-authsome) plugin for application authentication, but needed something similar to Auth for Authorization. I could have used Auth component, but that felt both hacky and sometimes tricky to use. Rather than spread the permissions across several controllers with complex rules in various places, I created the PermitComponent to centralize all of this into one `permit.php` file.

## Requirements

* CakeSession-accessible User session
* CakePHP 2.x

## Installation

For 1.3 support, please see the [1.3 branch](https://github.com/josegonzalez/sanction/tree/1.3).

_[Manual]_

* Download this: [https://github.com/josegonzalez/sanction/zipball/master](https://github.com/josegonzalez/sanction/zipball/master)
* Unzip that download.
* Copy the resulting folder to `app/Plugin`
* Rename the folder you just copied to `Sanction`

_[GIT Submodule]_

In your app directory type:

	git submodule add git://github.com/josegonzalez/sanction.git Plugin/Sanction
	git submodule init
	git submodule update

_[GIT Clone]_

In your plugin directory type

	git clone git://github.com/josegonzalez/sanction.git Sanction

### Enable plugin

In 2.0 you need to enable the plugin your `app/Config/bootstrap.php` file:

    CakePlugin::load('Sanction');

If you are already using `CakePlugin::loadAll();`, then this is not necessary.

## Usage

### PermitComponent Setup

Install the SanctionPlugin in your `app/Plugin/` folder under the `Sanction` folder.

If you want to use the `PermitComponent` to block user access to controllers/actions, add the following to your `AppController` or a specific Controller:

	/**
	* the 'path' key defines the Session key which holds user information (default Auth.User)
	* the 'check' key defined the subfield of the path, used to verify only that someone is logged in (default: id)
	*/


	// Example for default AuthComponent Configuration (with associated models)
	public $components = array('Sanction.Permit' => array(
		'path' => 'Auth',
		'check' => 'Auth.id'
	));

	// Example for default AuthComponent Configuration (no associated models, restricted to just looking at the User model)
	public $components = array('Sanction.Permit' => array(
		'path' => 'Auth.User'
	));

	// Example for default AuthsomeComponent Configuration (with associated models)
	public $components = array('Sanction.Permit' => array(
		'path' => 'User',
		'check' => 'User.id'
	));

	// Example for default AuthsomeComponent Configuration (no associated models, restricted to just looking at the User model)
	public $components = array('Sanction.Permit' => array(
		'path' => 'User.User'
	));

	// Example for customized AuthsomeComponent Configuration (with associated models)
	public $components = array(
		'Authsome.Authsome' => array(
			'model' => 'Member',
			'configureKey' => 'MemberAuth',
			'sessionKey' => 'MemberAuth',
			'cookieKey' => 'MemberAuth',
		),
		'Sanction.Permit' => array('path' => 'MemberAuth')
	);

	// NOTE: you can also configure the settings in the permit.php file (below) in which case, just include the component by itself (this way the configuration is available to the debug_kit panel)
	public $components = array('Sanction.Permit');

Then you will want to setup some permissions...

Create a file called `permit.php` in your `app/Config/` folder. You'll need to do the following:

* Add `App::uses('Permit', 'Sanction.Controller/Component');`
* Define some rules - these will be outlined below - using `Permit::access();`

You can test those rules by browsing to the url you are protecting.

### ClearanceHelper Setup

Install the SanctionPlugin in your `app/Plugin/` folder under the `Sanction` folder.

If you want to use the ClearanceHelper to block user access to controllers/actions, add the following to your AppController or a specific Controller:

	// Example (use the same {{$PATH}} as in the component)
	var $helpers = array('Sanction.Clearance' => array('path' => '{{$PATH}}'));

Then you will want to setup some permissions...

Create a file called `permit.php` in your `app/Config/` folder. You'll need to do the following:

* Add `App::uses('Permit', 'Sanction.Controller/Component');`
* Define some rules (these will be outlined below)

Then create links in your views using `$this->Clearance->link()` instead of `$this->Html->link()`. If the currently logged in user (or not logged in user) has access as defined by the `app/config/permit.php` file, then the link will appear in whatever view the ClearanceHelper was created in.

Note that checking against the `app/Config/permit.php` file is an expensive operation (for now!), so you might want to use this on links that will actually vary when displayed to different users, as opposed to all links in your application.

### DebugKit Panel

Included in the Sanction plugin is a `debug_kit` panel (as of commit fb061e57) which displays not only the currently matched access rule, but also all rules that you have defined for the application. Useful information if only so you can see whether the current request even went through a rule.

To use it, add the following to your AppController:


	// Example of how to implement the Sanction pannel
	var $components = array(
		'DebugKit.Toolbar' => array('panels' => array('Sanction.permit')),
	);

In the future, it will also keep a history of past requests, as well as highlighting explicitly which access rule was used.

### Rules

Rules are defined by `Permit::access();` and can contain 3 arrays:

* Array of rules upon which we will control access
* Array of rules by which the User's session must be defined by
** If you restrict to a single model and don't use associated data, you can enter just the fieldname to match on in the Auth array
** If you use associated models, you need to specify a `Set::extract()` path as the fieldname
* Array of extra parameters, such as where to redirect, the flash message, etc.

An example `app/Config/permit.php`:

	App::uses('Permit', 'Sanction.Controller/Component');

	// no associated model, example: looks for $sessionUser['group'] == 'admin'
	Permit::access(
		array('controller' => 'posts', 'action' => array('add', 'edit', 'delete')),
		array('auth' => array('group' => 'admin')),
		array('redirect' => array('controller' => 'users', 'action' => 'login'))
	);

	// with associated model, example: looks for in_array('admin',set::extract('/Group/name',$sessionUser))
	Permit::access(
		array('controller' => 'posts', 'action' => array('add', 'edit', 'delete')),
		array('auth' => array('Group.name' => 'admin')),
		array('redirect' => array('controller' => 'users', 'action' => 'login'))
	);

For the above, the following actions will be affected:

* `/posts/add/*`
* `/posts/edit/*`
* `/posts/delete/*`

The user must have be in the `admin` group (first example as a field on the user model, second as an assocaited Group model), and if they are not, they will be redirected to `/users/login`.

	App::uses('Permit', 'Sanction.Controller/Component');

	// no associated model, example: looks for $sessionUser['group'] == 'admin'
	Permit::access(
		array('controller' => 'comments', 'action' => array('edit', 'delete')),
		array('auth' => array('group' => 'admin')),
		array(
			'element' => 'comment',
			'key' => 'flash',
			'message' => __('You must be logged in to comment', true),
			'redirect' => array('controller' => 'users', 'action' => 'login')
		)
	);

For the above, the following actions will be affected:

* `/comments/edit/*`
* `/comments/delete/*`

The user must have a field 'group' with a value of `admin`, and if they do not, they will be redirected to `/users/login`. A flash message at the key `flash` using the `comment` element will also be displayed, which will contain the message '`You must be logged in to comment`'.

	App::uses('Permit', 'Sanction.Controller/Component');

	Permit::access(
		array('controller' => array('comments'), 'action' => array('add')),
		array('auth' => true),
		array(
			'message' => __('You must be logged in to comment', true),
			'redirect' => array('controller' => 'users', 'action' => 'login')
		)
	);

For the above, the following actions will be affected:

* `/comments/add/*`

The user must authenticated, and if they are not, they will be redirected to `/users/login`. A flash message will also be presented to them which will contain the message '`You must be logged in to comment`'.

	App::uses('Permit', 'Sanction.Controller/Component');

	// no associated model, example: looks for $sessionUser['group'] == 'admin'
	Permit::access(
		array('prefix' => 'admin'),
		array('auth' => array('group' => 'admin')),
		array('redirect' => array('admin' => false, 'controller' => 'users', 'action' => 'login')));

	// with associated model, example: looks for in_array('admin',set::extract('/Group/name',$sessionUser))
	Permit::access(
		array('prefix' => 'admin'),
		array('auth' => array('Group.name' => 'admin')),
		array('redirect' => array('admin' => false, 'controller' => 'users', 'action' => 'login'))
	);

For the above, any route with an admin prefix would be affected.

The user must be in the group `admin`, and if they are not, they will be redirected to `/users/login`.

## Todo

* More Unit Tests
* Document the Access Plugin
* Support yaml/db-backed Permit Component permission loading

## License

Copyright (c) 2010-2012 Jose Diaz-Gonzalez

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.