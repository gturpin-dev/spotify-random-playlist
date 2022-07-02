# Requirements

You must have ACF Pro installed to use this plugin.
You must import all ACF fields needed for this plugin.
You must have a Spotify premium account.

# Installation

- Download all plugins files from here
- Name the folder "spotify"
- Do a `composer install` in the folder
- Import the plugin in your WP
- Download the ACF fields that come with this plugin
- Go to your Spotify account 
  - Generate an app and store the information in the ACF option page "Spotify Credentials"
  - Store the client ID 
  - Store the client Secret
  - Store the Redirect URI ( the full URL of your site to the spotify-callback.php file ex. https://localhost:8000/wp-content/plugins/spotify/auth_callback.php)
- In the Spotify APP, create a new playlist and get the playlist ID
- Go to your WP profile
  - Set your Custom playlist ID
  - For each elem in the repeater, set the Artist name and its ID ( the name is only for you )
  - Set the number of tracks per artist
- Create a new page with this slug "spotify-credentials"
- Go to this page and connect to the Spotify account
- Accept the terms and conditions
- Your PL is ready to use