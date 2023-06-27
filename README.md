# Discord Bot (Laravel Discord PHP)

This PHP-based Discord bot, powered by the [discord-php](https://github.com/discord-php/DiscordPHP) library, is a hobby project aimed at expanding my knowledge of PHP and exploring the capabilities of the Discord API, as well as integrating with other APIs.

By developing this bot, I aim to gain hands-on experience in building and managing a Discord bot using PHP. Throughout the development process, I will be leveraging the functionality provided by the discord-php library to interact with the Discord API, enabling features such as command handling, event management, and more.

Furthermore, this project presents an opportunity for me to delve into other APIs and learn how to integrate them with my bot. For instance, I am exploring the use of the [translate-shell](https://github.com/soimort/translate-shell) for translation commands and leveraging the [Spotify API](https://developer.spotify.com/documentation/web-api) to provide music-related functionalities like song suggestions, top songs, and playlists.

Through this hobby project, I look forward to enhancing my PHP skills, deepening my understanding of the Discord and other APIs, and ultimately creating a useful and enjoyable Discord bot for users to interact with.

Furthermore, I am also using this project to explore the automation possibilities of file generation. For instance, I am using a PHP script to generate command files based on a template. This allows me to quickly generate new command files without having to manually create them and copy-paste the template. (Also the commands table below is generated using a PHP script)


## Installation

### Requirements

- PHP 8.2.5 or higher
- Composer
- [translate-shell](https://github.com/soimort/translate-shell) (for the translation command)
- [Spotify Application](https://developer.spotify.com/dashboard/applications) (for Spotify commands)
- Required tokens/secrets can be found in the `.env` file.

### Installation Steps

1. Clone the repository.
2. Run `composer install`.
3. Duplicate the `.env.example` file as `.env` and provide the required values.
4. Start the bot by running `php bot.php`.
5. Enjoy!


## Features

- [x] Basic commands
- [x] Basic event handling
- [x] Basic command template

## Generate command files

To generate a new command file, run `php MakeCommand.php [Command] [Command Directory]`. This will create a new command file in the `src/Commands/[Command Directory]` directory. The command file will be named `[Command].php` and will contain a basic template for a command.
## Slash Commands

| Category         | Command                                        | Description                                                                |
|------------------|------------------------------------------------|----------------------------------------------------------------------------|
| Commands   | `/help [command] [ephemeral]`                       | Show all commands                                 |
| Commands   | `/ping [ephemeral]`                       | Ping the bot to check if it is online                                 |
| Commands   | `/search [query] [safe] [ephemeral]`                       | Search google for a query                                 |
| Commands   | `/translate [text] [to] [from] [ephemeral]`                       | translate text                                 |
| Commands   | `/job [ephemeral]`                       | Ping the bot to check if it is online                                 |
| Github   | `/updateself [ephemeral]`                       | Update the bot                                 |
| Spotify   | `/currentsong [ephemeral]`                       | Share the song you are currently listening to                                 |
| Spotify   | `/latestsongs [amount]`                       | Get the latest song from your liked songs                                 |
| Spotify   | `/playlistgen [startdate] [public] [ephemeral]`                       | Generate a playlist from within a time frame                                 |
| Spotify   | `/playlists `                       | Get your playlists                                 |
| Spotify   | `/songsuggestions [amount] [genre] [ephemeral] [mood] [queue]`                       | Get song suggestions based on your top songs                                 |
| Spotify   | `/spotify [select] [ephemeral]`                       | Allow the bot to access your spotify account                                 |
| Spotify   | `/topsongs [amount]`                       | Get the top songs from your liked songs                                 |
| Weather   | `/astronomy [country] [city] [ephemeral]`                       | Get the astronomical data for today                                 |
| Weather   | `/forecast [country] [city] [ephemeral]`                       | Get the forecast for the next 3 days                                 |
| Weather   | `/weather [country] [city] [country2] [city2] [ephemeral]`                       | Get the current weather                                 |
## Notes

- This bot is currently under development and may contain bugs.
- Some commands, like `/Songsuggestions [playlist=true]`, may result in multiple incomplete playlists.
