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

The main

## Requirements 📄

To use this REST API you will need Composer and a Stripe Account.

### Node

- #### Node installation on Windows

  Just go on [official Node.js website](https://nodejs.org/) and download the installer.
  Also, be sure to have `git` available in your PATH, `npm` might need it (You can find git [here](https://git-scm.com/)).

- #### Node installation on Ubuntu

  You can install nodejs and npm easily with apt install, just run the following commands.

      $ sudo apt install nodejs
      $ sudo apt install npm

- #### Other Operating Systems
  You can find more information about the installation on the [official Node.js website](https://nodejs.org/) and the [official NPM website](https://npmjs.org/).

If the installation was successful, you should be able to run the following command.

    $ node --version
    v10.14.2

    $ npm --version
    6.4.1

If you need to update `npm`, you can make it using `npm`! Cool right? After running the following command, just open again the command line and be happy.

    $ npm install npm -g

---

## Install 📥

    $ git clone https://github.com/AyazBulls/karaok-guard
    $ cd karaok-guard
    $ npm install

## Configure app 🔧

Open `config.json` then edit it with your settings. You will need:

- BOT_TOKEN: Bot token provided by Discord. [More Info](https://www.writebots.com/discord-bot-token/)
- PREFIX: The prefix which will be user to send commands to the bot
- ADMIN_ROLE: The role required to play with the bot
- PARENT_ID: ID of the parent channel which contains the *karaoke booths*

## Running the project 🚀

    $ npm start

## Simple build for production 🔨

    $ npm build
