# Filament Impersonate

[![Latest Version on Packagist](https://img.shields.io/packagist/v/stechstudio/filament-impersonate.svg?style=flat-square)](https://packagist.org/packages/stechstudio/filament-impersonate)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

This is a plugin for [Filament](https://filamentadmin.com/) that makes it easy to impersonate your users. 

### Credit

This package uses [https://github.com/404labfr/laravel-impersonate](https://github.com/404labfr/laravel-impersonate) under the hood, and borrows heavily from [https://github.com/KABBOUCHI/nova-impersonate](https://github.com/KABBOUCHI/nova-impersonate).

## Installation

You know the drill:

```bash
composer install stechstudio/filament-impersonate
```

## Quickstart

### 1. Add `Resource` action

First open the resource where you want the impersonate action to appear. This is generally going to be your `UserResoure` class.

Go down to the `table` method. After defining the table columns, you want to `addRecordsActions` and provide `Impersonate::make` as a new action for the table. Your class should look like this:

```php
namespace App\Filament\Resources;

use Filament\Resources\Resource;
use STS\FilamentImpersonate\Impersonate;

class UserResource extends Resource {
    public static function table(Table $table)
    {
        return $table
            ->columns([
                // ...
            ])
            ->prependActions([
                Impersonate::make('impersonate'), // <--- 
            ]);
    }
```

### 2. Add the trait to your List page

Open the ListRecords page for your resource. If you were using the `UserResource` above, then you should be opening the `UserResource/Pages/ListUsers` class.

Then add the `CanImpersonateUsers` trait, like this:

```php
namespace App\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\ListRecords;
use STS\FilamentImpersonate\CanImpersonateUsers;

class ListUsers extends ListRecords
{
    use CanImpersonateUsers;

    public static $resource = UserResource::class;
``` 

### 3. Add the banner to your blade layout

The last step is to display a notice in your app whenever you are impersonating another user. Open up your master layout file and add `<x-impersonate::banner/>` before the closing `</body>` tag.

### 4. Profit!

That's it. You should now see an action icon next to each user in your Filament `UserResource` list:

<img width="1088" alt="CleanShot 2021-03-28 at 20 23 09@2x" src="https://user-images.githubusercontent.com/203749/112773305-84aa7f80-9003-11eb-9014-043f29768cb7.png">

When you click on the impersonate icon you will be logged in as that user, and redirected to your main app. You will see the impersonation banner at the top of the page, with a button to leave and return to Filament:

![banner](https://user-images.githubusercontent.com/203749/112773267-5331b400-9003-11eb-85ae-b54c458fb5aa.png)


## Configuration

All configuration can be managed with ENV variables, no need to publish and edit the config directly. Just check out the [config file](/config/filament-impersonate.php).

## Authorization

By default, only Filament admins can impersonate other users. You can control this by adding a `canImpersonate` method to your `FilamentUser` class:

```php
class User implements FilamentUser {
    use IsFilamentUser;
    
    public function canImpersonate()
    {
        return true;
    }
}
```

You can also control which targets can *be* impersonated. Just add a `canBeImpersonated` method to the user class with whatever logic you need:

```php
class User {
    public function canBeImpersonated()
    {
        // Let's prevent impersonating other users at our own company
        return !Str::endsWith($this->email, '@mycorp.com');
    }
}
``` 

## Customizing the banner

The blade component has a few options you can customize. 

### Style

The banner is dark by default, you can set this to light:

```html
<x-impersonate::banner style='light'/>
```

### Display name

The banner will show the name of the impersonated user, assuming there is a `name` attribute. You can customize this if needed:

```html
<x-impersonate::banner :display='auth()->user()->email'/>
```

