<div align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="laravel-logo">
  <h1>API Mango 🥭</h1>

  <p>REST API made with the web framework Laravel.</p>
</div>

## Resources 📝

* [Laravel](https://laravel.com/)
* [Stripe API](https://stripe.com/docs/api)
* [Navitia API](https://www.navitia.io/)

I'd highly recommend reading through some of the Laravel, Stripe and Navitia documentation.

## Project 🚧

This REST API is part of a bigger project named *Goyave*. *Govaye* is a mobile app which generates journeys based on the user's likings. The journey are generated according multiple parameters: Localisation 📍, Time 🕑, Budget 💰 and Activity category 📁.

## REST API Actions ⚙️

This API handles the secured logins of users (customers, administrators and restaurant owners) with [OAuth2](https://oauth.net/2/) through [Passport](https://laravel.com/docs/7.x/passport). It also includes [Stripe](https://stripe.com/) in order to handle the SaaS part of the project (for the restaurant owners).

To get a journey containing all the activities selected, we use the [Navitia Open API](https://www.navitia.io/).

The main functionnality of the API is contained in the *SearchController.php*, it calculates the best journey according the user's filters.

## Requirements 📄

To use this REST API you will need Composer, a Stripe account and a Navitia account.

### Composer

- #### Composer installation on Windows

  Just go on [official Composer website](https://getcomposer.org/) and download the installer.
  Also, be sure to have `git` available in your PATH, `npm` might need it (You can find git [here](https://git-scm.com/).

- #### Composer installation on Ubuntu

  Just go on [official Composer website](https://getcomposer.org/) and download the installer.
  Also, be sure to have `git` available in your PATH, `npm` might need it (You can find git [here](https://git-scm.com/).

- #### Other Operating Systems
  You can find more information about the installation on the [official Composer website](https://getcomposer.org/).

---

## Install 📥

    $ git clone https://github.com/AyazBulls/api-mango.git
    $ cd api-mango
    $ composer install
    $ cp .env.example .env
    $ php artisan key:generate
    $ php artisan migrate
    $ php artisan serve
    $ php artisan passport:install

## Configure app 🔧

Open `.env` then edit it with your settings. You will need:
- MAIL:...

## Running the project 🚀

    $ php artisan serve
